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

use Twocents\Value\Comment;

class Db
{
    /** @var resource */
    protected static $lockFile;

    public static function getFoldername(): string
    {
        global $pth;

        $foldername = $pth['folder']['content'] . 'twocents/';
        if (!file_exists($foldername)) {
            mkdir($foldername, 0777, true);
            chmod($foldername, 0777);
        }
        $lockFilename = $foldername . '.lock';
        if (!file_exists($lockFilename)) {
            touch($lockFilename);
        }
        return $foldername;
    }

    /** @return void */
    public static function lock(int $operation)
    {
        switch ($operation) {
            case LOCK_SH:
            case LOCK_EX:
                self::$lockFile = fopen(self::getLockFilename(), 'r');
                flock(self::$lockFile, $operation);
                break;
            case LOCK_UN:
                flock(self::$lockFile, $operation);
                fclose(self::$lockFile);
                break;
        }
    }

    /** @return list<string> */
    public static function findAllTopics(string $extension = "csv"): array
    {
        $topics = array();
        Db::lock(LOCK_SH);
        if ($dir = opendir(Db::getFoldername())) {
            while (($entry = readdir($dir)) !== false) {
                if (pathinfo($entry, PATHINFO_EXTENSION) === $extension) {
                    $topics[] = basename($entry, ".$extension");
                }
            }
            closedir($dir);
        }
        Db::lock(LOCK_UN);
        return $topics;
    }

    /** @return list<Comment> */
    public static function findTopic(string $topic, bool $visibleOnly = false): array
    {
        Db::lock(LOCK_SH);
        $comments = [];
        $filename = Db::getFoldername() . $topic . ".csv";
        if (is_readable($filename) && ($file = fopen($filename, 'r'))) {
            while (($record = fgetcsv($file)) !== false) {
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
        Db::lock(LOCK_UN);
        return $comments;
    }

    /** @return list<Comment> */
    public static function findGbookTopic(string $topic): array
    {
        Db::lock(LOCK_SH);
        $comments = [];
        $filename = Db::getFoldername() . $topic . ".txt";
        if (($file = fopen($filename, 'r'))) {
            while (($line = fgets($file)) !== false) {
                $record = explode(';', trim($line));
                $comments[] = new Comment(
                    uniqid(),
                    $topic,
                    $record[8] ?? strtotime("{$record[4]} {$record[3]}"),
                    $record[0],
                    $record[1],
                    "<p><strong>{$record[5]}</strong></p><p>{$record[6]}</p>",
                    ($record[11] ?? "yes") !== "yes"
                );
            }
            fclose($file);
        }
        Db::lock(LOCK_UN);
        return $comments;
    }

    /** @return list<Comment> */
    public static function findCommentsTopic(string $topic): array
    {
        Db::lock(LOCK_SH);
        $comments = [];
        $filename = Db::getFoldername() . $topic . ".txt";
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
        Db::lock(LOCK_UN);
        return $comments;
    }

    /**
     * @param list<Comment> $comments
     * @return void
     */
    public static function storeTopic(string $topic, array $comments)
    {
        Db::lock(LOCK_EX);
        $filename = Db::getFoldername() . $topic . ".csv";
        if (($file = fopen($filename, "w"))) {
            foreach ($comments as $comment) {
                fputcsv($file, $comment->toRecord());
            }
            fclose($file);
        }
        Db::lock(LOCK_UN);
    }

    protected static function getLockFilename(): string
    {
        return self::getFoldername() . '.lock';
    }
}
