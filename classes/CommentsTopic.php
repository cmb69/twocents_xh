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

namespace Twocents;

class CommentsTopic extends Topic
{
    const EXT = 'txt';

    /** @return list<Topic> */
    public static function findAll(): array
    {
        $topics = array();
        Db::lock(LOCK_SH);
        if ($dir = opendir(Db::getFoldername())) {
            while (($entry = readdir($dir)) !== false) {
                if (pathinfo($entry, PATHINFO_EXTENSION) == self::EXT) {
                    $topics[] = self::load(basename($entry, '.' . self::EXT));
                }
            }
            closedir($dir);
        }
        Db::lock(LOCK_UN);
        return $topics;
    }

    protected static function load(string $name): Topic
    {
        return new self($name);
    }
}
