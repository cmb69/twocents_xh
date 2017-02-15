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

class Db
{
    /**
     * @var resource
     */
    protected static $lockFile;

    /**
     * @return string
     */
    public static function getFoldername()
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

    /**
     * @param int $operation A lock operation (LOCK_SH, LOCK_EX or LOCK_UN).
     */
    public static function lock($operation)
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

    /**
     * @return string
     */
    protected static function getLockFilename()
    {
        return self::getFoldername() . '.lock';
    }
}
