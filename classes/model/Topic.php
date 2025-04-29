<?php

/**
 * Copyright 2023 Christoph M. Becker
 *
 * This file is part of Twocents_XH.
 *
 * Twocents_XH is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Twocents_XH is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Twocents_XH.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace Twocents\Model;

use Plib\Document;
use Plib\DocumentStore;

final class Topic implements Document
{
    /** @var array<string,Comment> */
    private $comments = [];

    public static function fromString(string $contents, string $key): self
    {
        if (preg_match('/\.csv$/', $key)) {
            return self::fromCsvString($contents, basename($key, ".csv"));
        }
        if (strpos($contents, "-,+;-") !== false) {
            return self::fromCommentsString($contents, basename($key, ".txt"));
        }
        return self::fromGbookString($contents, basename($key, ".txt"));
    }

    private static function fromCsvString(string $contents, string $topicname): self
    {
        $that = new self();
        $stream = fopen("php://memory", "w+");
        if ($stream === false) {
            return $that;
        }
        if (fwrite($stream, $contents) !== strlen($contents)) {
            fclose($stream);
            return $that;
        }
        if (!rewind($stream)) {
            fclose($stream);
            return $that;
        }
        while (($record = fgetcsv($stream, 0, ",", "\"", "\0")) !== false) {
            assert($record !== null);
            if ($record[0] === null || count($record) < 5) {
                continue;
            }
            assert(is_string($record[1]) && is_string($record[2])
                && is_string($record[3]) && is_string($record[4]));
            $that->comments[$record[0]] = new Comment(
                $record[0],
                $topicname,
                (int) $record[1],
                $record[2],
                $record[3],
                $record[4],
                isset($record[5]) ? (bool) $record[5] : false
            );
        }
        fclose($stream);
        return $that;
    }

    private static function fromCommentsString(string $contents, string $topicname): self
    {
        $that = new self();
        $lines = preg_split('/(\r)?\n/', $contents);
        if ($lines === false) {
            return $that;
        }
        $lines = array_slice($lines, 1);
        foreach ($lines as $line) {
            $record = explode("-,+;-", $line);
            if (count($record) < 8) {
                continue;
            }
            $id = uniqid(); // TODO: better ID
            $that->comments[$id] = new Comment(
                $id,
                $topicname,
                (int) $record[5],
                $record[1],
                $record[2],
                $record[7],
                $record[0] === "hidden"
            );
        }
        return $that;
    }

    private static function fromGbookString(string $contents, string $topicname): self
    {
        $that = new self();
        $lines = preg_split('/(\r)?\n/', $contents);
        if ($lines === false) {
            return $that;
        }
        foreach ($lines as $line) {
            $record = explode(';', $line);
            if (count($record) < 7) {
                continue;
            }
            $id = uniqid(); // TODO: better ID
            $that->comments[$id] = new Comment(
                $id,
                $topicname,
                (int) ($record[8] ?? strtotime("{$record[4]} {$record[3]}")),
                $record[0],
                $record[1],
                "<p><strong>{$record[5]}</strong></p><p>{$record[6]}</p>",
                ($record[11] ?? "yes") !== "yes"
            );
        }
        return $that;
    }

    /** @return list<string> */
    public static function all(DocumentStore $store): array
    {
        return array_map(function (string $filename) {
            return basename($filename, ".csv");
        }, $store->find('/\.csv$/'));
    }

    /** @return list<string> */
    public static function legacy(DocumentStore $store): array
    {
        return array_map(function (string $filename) {
            return basename($filename, ".txt");
        }, $store->find('/\.txt$/'));
    }

    public static function retrieve(string $name, DocumentStore $store): self
    {
        $filenames = $store->find("/^$name\.(?:csv|txt)$/");
        if (in_array("$name.csv", $filenames)) {
            $filename = "$name.csv";
        } else {
            $filename = "$name.txt";
        }
        $that = $store->retrieve($filename, self::class);
        assert($that instanceof self);
        return $that;
    }

    public static function update(string $name, DocumentStore $store): self
    {
        $that = $store->update("$name.csv", self::class);
        assert($that instanceof self);
        return $that;
    }

    public function toString(): string
    {
        $contents = "";
        $stream = fopen("php://memory", "w+");
        if ($stream === false) {
            return $contents;
        }
        foreach ($this->comments as $comment) {
            if (!fputcsv($stream, $comment->toRecord(), ",", "\"", "\0")) {
                fclose($stream);
                return $contents;
            }
        }
        if (!rewind($stream)) {
            fclose($stream);
            return $contents;
        }
        $contents = (string) stream_get_contents($stream);
        fclose($stream);
        return $contents;
    }

    /** @return list<Comment> */
    public function comments(): array
    {
        return array_values($this->comments);
    }

    public function comment(string $id): ?Comment
    {
        if (!array_key_exists($id, $this->comments)) {
            return null;
        }
        return $this->comments[$id];
    }

    /** @return list<Comment> */
    public function visibleComments(): array
    {
        $res = [];
        foreach ($this->comments as $comment) {
            if (!$comment->hidden()) {
                $res[] = $comment;
            }
        }
        return $res;
    }

    public function addComment(Comment $comment): void
    {
        assert($comment->id() !== null);
        assert(!array_key_exists($comment->id(), $this->comments));
        $this->comments[$comment->id()] = $comment;
    }

    public function updateComment(Comment $comment): void
    {
        assert($comment->id() !== null);
        assert(array_key_exists($comment->id(), $this->comments));
        $this->comments[$comment->id()] = $comment;
    }

    public function deleteComment(string $id): void
    {
        assert(array_key_exists($id, $this->comments));
        unset($this->comments[$id]);
    }
}
