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
    /** @var array<Comment> */
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
            $comment = new Comment(
                $record[0],
                $topicname,
                (int) $record[1],
                $record[2],
                $record[3],
                $record[4],
                isset($record[5]) ? (bool) $record[5] : false
            );
            if ($record[0] === "") {
                $that->comments[] = $comment;
            } else {
                $that->comments[$record[0]] = $comment;
            }
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
            $that->comments[] = new Comment(
                null,
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
            $that->comments[] = new Comment(
                null,
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

    /**
     * @phpstan-param callable():string $genId
     * @phpstan-param callable(string):string $convert
     */
    public static function retrieve(
        string $name,
        DocumentStore $store,
        ?callable $genId = null,
        ?callable $convert = null
    ): self {
        $filenames = $store->find("/^$name\.(?:csv|txt)$/");
        if (in_array("$name.csv", $filenames)) {
            $ext = "csv";
        } else {
            $ext = "txt";
        }
        $that = $store->retrieve("$name.$ext", self::class);
        assert($that instanceof self);
        if ($ext === "txt") {
            $that = self::migrate($that, $name, $store, $genId, $convert);
        } elseif ($genId !== null) {
            $ok = true;
            foreach ($that->comments as $comment) {
                if ($comment->id() === "") {
                    $ok = false;
                    break;
                }
            }
            if (!$ok) {
                $that = self::generateIds($name, $store, $genId);
            }
        }
        return $that;
    }

    /**
     * @phpstan-param callable():string $genId
     * @phpstan-param callable(string):string $convert
     */
    private static function migrate(
        self $that,
        string $name,
        DocumentStore $store,
        ?callable $genId = null,
        ?callable $convert = null
    ): self {
        $newtopic = $store->update("$name.csv", self::class);
        assert($newtopic instanceof self);
        foreach ($that->comments as $comment) {
            if (($comment->id() === null || $comment->id() === "") && $genId !== null) {
                $comment->setId($genId());
            }
            if ($convert !== null) {
                $message = $convert($comment->message());
                $comment->setMessage($message);
            }
            $newtopic->addComment($comment);
        }
        $store->commit();
        $that = $store->retrieve("$name.csv", self::class);
        assert($that instanceof self);
        return $that;
    }

    /** @phpstan-param callable():string $genId */
    private static function generateIds(string $name, DocumentStore $store, callable $genId): self
    {
        $newtopic = $store->update("$name.csv", self::class);
        assert($newtopic instanceof self);
        foreach ($newtopic->comments as $id => $comment) {
            if ($comment->id() === "") {
                $comment->setId($genId());
            }
            unset($newtopic->comments[$id]);
            $newtopic->comments[$comment->id()] = $comment;
        }
        $store->commit();
        $that = $store->retrieve("$name.csv", self::class);
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

    public function deleteComment(string $id): void
    {
        assert(array_key_exists($id, $this->comments));
        unset($this->comments[$id]);
    }
}
