<?php

/**
 * The topics of the Comments Plugin.
 *
 * PHP version 5
 *
 * @category  CMSimple_XH
 * @package   Twocents
 * @author    Christoph M. Becker <cmbecker69@gmx.de>
 * @copyright 2014 Christoph M. Becker <http://3-magi.net/>
 * @license   http://www.gnu.org/licenses/gpl-3.0.en.html GNU GPLv3
 * @link      http://3-magi.net/?CMSimple_XH/Twocents_XH
 */

/**
 * The topics of the Comments Plugin.
 *
 * @category CMSimple_XH
 * @package  Twocents
 * @author   Christoph M. Becker <cmbecker69@gmx.de>
 * @license  http://www.gnu.org/licenses/gpl-3.0.en.html GNU GPLv3
 * @link     http://3-magi.net/?CMSimple_XH/Twocents_XH
 */
class Twocents_CommentsTopic extends Twocents_Topic
{
    /**
     * The file extension.
     */
    const EXT = 'txt';

    /**
     * Returns all topics.
     *
     * @return array
     */
    public static function findAll()
    {
        $topics = array();
        Twocents_Db::lock(LOCK_SH);
        if ($dir = opendir(Twocents_Db::getFoldername())) {
            while (($entry = readdir($dir)) !== false) {
                if (pathinfo($entry, PATHINFO_EXTENSION) == self::EXT) {
                    $topics[] = self::_load(basename($entry, '.' . self::EXT));
                }
            }
        }
        closedir($dir);
        Twocents_Db::lock(LOCK_UN);
        return $topics;
    }

    /**
     * Loads a topic and returns it.
     *
     * @param string $name A topicname.
     *
     * @return Twocents_Topic
     */
    private static function _load($name)
    {
        return new self($name);
    }
}

?>
