<?php

/**
 * Copyright 2014-2023 Christoph M. Becker
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

namespace Twocents\Infra;

use Error;
use Twocents\Value\Comment;

class Db
{
    /** @var string */
    private $foldername;

    public function __construct(string $foldername)
    {
        $this->foldername = $foldername;
    }

    public function getFoldername(): string
    {
        if (!file_exists($this->foldername)) {
            mkdir($this->foldername, 0777, true);
            chmod($this->foldername, 0777);
        }
        $lockFilename = $this->foldername . '.lock';
        if (!file_exists($lockFilename)) {
            touch($lockFilename);
        }
        return $this->foldername;
    }

    /** @return resource|null */
    protected function lock(bool $exclusive)
    {
        $lock = fopen($this->getLockFilename(), 'r');
        if ($lock === false) {
            return null;
        }
        flock($lock, $exclusive ? LOCK_EX : LOCK_SH);
        return $lock;
    }

    /**
     * @param resource|null $lock
     * @return void
     */
    protected function unlock($lock)
    {
        if ($lock === null) {
            return;
        }
        flock($lock, LOCK_UN);
        fclose($lock);
    }

    /** @return list<string> */
    public function findTopics(string $extension = "csv"): array
    {
        $topics = array();
        $lock = $this->lock(false);
        if ($dir = opendir($this->getFoldername())) {
            while (($entry = readdir($dir)) !== false) {
                if (pathinfo($entry, PATHINFO_EXTENSION) === $extension) {
                    $topics[] = basename($entry, ".$extension");
                }
            }
            closedir($dir);
        }
        $this->unlock($lock);
        return $topics;
    }

    /** @return list<Comment> */
    public function findCommentsOfTopic(string $topic, bool $visibleOnly = false): array
    {
        $lock = $this->lock(false);
        $comments = [];
        $filename = $this->getFoldername() . $topic . ".csv";
        if (is_readable($filename) && ($file = fopen($filename, 'r'))) {
            while (($record = fgetcsv($file, 0, ",", "\"", "\0")) !== false) {
                $hidden = isset($record[5]) ? (bool) $record[5] : false;
                if ($visibleOnly && $hidden) {
                    continue;
                }
                $comments[] = new Comment(
                    $record[0],
                    $topic,
                    (int) $record[1],
                    $record[2],
                    $record[3],
                    $record[4],
                    isset($record[5]) ? (bool) $record[5] : false
                );
            }
            fclose($file);
        }
        $this->unlock($lock);
        return $comments;
    }

    /** @return list<Comment> */
    public function findCommentsOfGbookTopic(string $topic): array
    {
        $lock = $this->lock(false);
        $comments = [];
        $filename = $this->getFoldername() . $topic . ".txt";
        if (($file = fopen($filename, 'r'))) {
            while (($line = fgets($file)) !== false) {
                $record = explode(';', trim($line));
                $comments[] = new Comment(
                    uniqid(),
                    $topic,
                    (int) ($record[8] ?? strtotime("{$record[4]} {$record[3]}")),
                    $record[0],
                    $record[1],
                    "<p><strong>{$record[5]}</strong></p><p>{$record[6]}</p>",
                    ($record[11] ?? "yes") !== "yes"
                );
            }
            fclose($file);
        }
        $this->unlock($lock);
        return $comments;
    }

    /** @return list<Comment> */
    public function findCommentsOfCommentsTopic(string $topic): array
    {
        $lock = $this->lock(false);
        $comments = [];
        $filename = $this->getFoldername() . $topic . ".txt";
        if (($file = fopen($filename, 'r'))) {
            if (fgets($file) !== false) {
                while (($line = fgets($file)) !== false) {
                    $record = explode("-,+;-", trim($line));
                    $comments[] = new Comment(
                        uniqid(),
                        $topic,
                        (int) $record[5],
                        $record[1],
                        $record[2],
                        $record[7],
                        $record[0] === "hidden"
                    );
                }
            }
            fclose($file);
        }
        $this->unlock($lock);
        return $comments;
    }

    /**
     * @param list<Comment> $comments
     * @return void
     */
    public function storeTopic(string $topic, array $comments)
    {
        $lock = $this->lock(true);
        $filename = $this->getFoldername() . $topic . ".csv";
        if (($file = fopen($filename, "w"))) {
            foreach ($comments as $comment) {
                if ($comment->topicname() !== $topic) {
                    throw new Error("topic mismatch");
                }
                fputcsv($file, $comment->toRecord(), ",", "\"", "\0");
            }
            fclose($file);
        }
        $this->unlock($lock);
    }

    public function findComment(string $topic, string $id): ?Comment
    {
        $lock = $this->lock(false);
        $comment = null;
        $filename = $this->getFoldername() . $topic . ".csv";
        if (is_readable($filename) && ($file = fopen($filename, 'r'))) {
            while (($record = fgetcsv($file, 0, ",", "\"", "\0")) !== false) {
                if ($record[0] === $id) {
                    $comment = new Comment(
                        $record[0],
                        $topic,
                        (int) $record[1],
                        $record[2],
                        $record[3],
                        $record[4],
                        isset($record[5]) ? (bool) $record[5] : false
                    );
                    break;
                }
            }
            fclose($file);
        }
        $this->unlock($lock);
        return $comment;
    }

    public function insertComment(Comment $comment): bool
    {
        $lock = $this->lock(true);
        $file = fopen($this->getFoldername() . $comment->topicname() . ".csv", "a");
        if ($file === false) {
            return false;
        }
        if (fputcsv($file, $comment->toRecord(), ",", "\"", "\0") === false) {
            return false;
        }
        fclose($file);
        $this->unlock($lock);
        return true;
    }

    public function updateComment(Comment $comment): bool
    {
        return $this->modify($comment, function ($stream, array $record) use ($comment) {
            if ($record[0] != $comment->id()) {
                fputcsv($stream, $record, ",", "\"", "\0");
            } else {
                fputcsv($stream, $comment->toRecord(), ",", "\"", "\0");
            }
        });
    }

    public function deleteComment(Comment $comment): bool
    {
        return $this->modify($comment, function ($stream, array $record) use ($comment) {
            if ($record[0] != $comment->id()) {
                fputcsv($stream, $record, ",", "\"", "\0");
            }
        });
    }

    /** @param callable(resource,array<string>):void $fun */
    private function modify(Comment $comment, callable $fun): bool
    {
        $lock = $this->lock(true);
        $file = fopen($this->getFoldername() . $comment->topicname() . ".csv", "r+");
        if ($file === false) {
            return false;
        }
        $temp = fopen('php://temp', "w+");
        if ($temp === false) {
            return false;
        }
        while (($record = fgetcsv($file, 0, ",", "\"", "\0")) !== false) {
            $fun($temp, $record);
        }
        if (!ftruncate($file, 0)) {
            return false;
        }
        if (!rewind($file)) {
            return false;
        }
        if (!rewind($temp)) {
            return false;
        }
        if (stream_copy_to_stream($temp, $file) === false) {
            return false;
        }
        fclose($temp);
        fclose($file);
        $this->unlock($lock);
        return true;
    }

    protected function getLockFilename(): string
    {
        return $this->getFoldername() . '.lock';
    }
}
