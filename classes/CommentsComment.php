<?php

/**
 * Copyright 2014-2017 Christoph M. Becker
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

class CommentsComment extends Comment
{
    const EXT = 'txt';

    /**
     * @param string $name
     * @return Comment[]
     */
    public static function findByTopicname($name, $visibleOnly = false, $ascending = true)
    {
        $comments = array();
        Db::lock(LOCK_SH);
        $filename = Db::getFoldername() . $name . '.' . self::EXT;
        if (is_readable($filename) && ($file = fopen($filename, 'r'))) {
            if (fgets($file) !== false) {
                while (($line = fgets($file)) !== false) {
                    $record = explode('-,+;-', trim($line));
                    $comments[] = self::load($name, $record);
                }
            }
            fclose($file);
        }
        Db::lock(LOCK_UN);
        return $comments;
    }

    /**
     * @param string $topicname
     * @return Comment
     */
    protected static function load($topicname, array $record)
    {
        // image is $record[6]
        $comment = new parent($topicname, $record[5]);
        $comment->user = $record[1];
        $comment->email = $record[2];
        $comment->message = $record[7];
        $comment->hidden = $record[0] == 'hidden';
        return $comment;
    }
}
