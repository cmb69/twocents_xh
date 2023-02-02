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

class Topic
{
    const EXT = 'csv';

    /**
     * @return list<Topic>
     */
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

    /**
     * @return ?Topic
     */
    public static function findByName(string $name)
    {
        if (file_exists(Db::getFoldername() . $name . '.' . self::EXT)) {
            return self::load($name);
        } else {
            return null;
        }
    }

    protected static function load(string $name): Topic
    {
        return new self($name);
    }

    /**
     * @var string
     */
    protected $name;

    public function __construct(string $name)
    {
        $this->name = (string) $name;
    }

    public function getName(): string
    {
        return $this->name;
    }

    /** @return void */
    public function insert()
    {
        Db::lock(LOCK_EX);
        touch(Db::getFoldername() . $this->name . '.' . self::EXT);
        Db::lock(LOCK_UN);
    }

    /** @return void */
    public function delete()
    {
        Db::lock(LOCK_EX);
        unlink(Db::getFoldername() . $this->name . '.' . self::EXT);
        Db::lock(LOCK_UN);
    }
}
