<?php

/**
 * Copyright 2017-2023 Christoph M. Becker
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

namespace Twocents;

class GbookComment extends Comment
{
    const EXT = 'txt';

    /** @return list<Comment> */
    public static function findByTopicname(string $name, bool $visibleOnly = false, bool $ascending = true): array
    {
        $comments = array();
        Db::lock(LOCK_SH);
        $filename = Db::getFoldername() . $name . '.' . self::EXT;
        if (is_readable($filename) && ($file = fopen($filename, 'r'))) {
            if (fgets($file) !== false) {
                while (($line = fgets($file)) !== false) {
                    $record = explode(';', trim($line));
                    $comments[] = self::load($name, $record);
                }
            }
            fclose($file);
        }
        Db::lock(LOCK_UN);
        return $comments;
    }

    protected static function load(string $topicname, array $record): Comment
    {
        $comment = new parent($topicname, (int) $record[8]);
        $comment->user = $record[0];
        $comment->email = $record[1];
        $comment->message = "<p><strong>{$record[5]}</strong></p><p>{$record[6]}</p>";
        $comment->hidden = false;
        return $comment;
    }
}
