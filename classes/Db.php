<?php

/**
 * The data base.
 *
 * PHP version 5
 *
 * @category  CMSimple_XH
 * @package   Twocents
 * @author    Christoph M. Becker <cmbecker69@gmx.de>
 * @copyright 2014-2015 Christoph M. Becker <http://3-magi.net/>
 * @license   http://www.gnu.org/licenses/gpl-3.0.en.html GNU GPLv3
 * @link      http://3-magi.net/?CMSimple_XH/Twocents_XH
 */

/**
 * The data base.
 *
 * @category CMSimple_XH
 * @package  Twocents
 * @author   Christoph M. Becker <cmbecker69@gmx.de>
 * @license  http://www.gnu.org/licenses/gpl-3.0.en.html GNU GPLv3
 * @link     http://3-magi.net/?CMSimple_XH/Twocents_XH
 */
class Twocents_Db
{
    /**
     * The lock file handle.
     *
     * @var resource
     */
    protected static $lockFile;

    /**
     * Returns the path of the data folder.
     *
     * @return string
     *
     * @global array The paths of system files and folders.
     */
    public static function getFoldername()
    {
        global $pth;

        $foldername = $pth['folder']['content'] . 'twocents/';
        if (!file_exists($foldername)) {
            mkdir($foldername, 0777, true);
        }
        $lockFilename = $foldername . '.lock';
        if (!file_exists($lockFilename)) {
            touch($lockFilename);
        }
        return $foldername;
    }

    /**
     * (Un)locks the database.
     *
     * @param int $operation A lock operation (LOCK_SH, LOCK_EX or LOCK_UN).
     *
     * @return void
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
     * Returns the path of the lock file.
     *
     * @return string
     */
    protected static function getLockFilename()
    {
        return self::getFoldername() . '.lock';
    }

}

?>
