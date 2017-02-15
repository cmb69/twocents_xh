<?php

/**
 * The topics of the Comments Plugin.
 *
 * PHP version 5
 *
 * @category  CMSimple_XH
 * @package   Twocents
 * @author    Christoph M. Becker <cmbecker69@gmx.de>
 * @copyright 2014-2017 Christoph M. Becker <http://3-magi.net/>
 * @license   http://www.gnu.org/licenses/gpl-3.0.en.html GNU GPLv3
 * @link      http://3-magi.net/?CMSimple_XH/Twocents_XH
 */

namespace Twocents;

/**
 * The topics of the Comments Plugin.
 *
 * @category CMSimple_XH
 * @package  Twocents
 * @author   Christoph M. Becker <cmbecker69@gmx.de>
 * @license  http://www.gnu.org/licenses/gpl-3.0.en.html GNU GPLv3
 * @link     http://3-magi.net/?CMSimple_XH/Twocents_XH
 */
class CommentsTopic extends Topic
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
        Db::lock(LOCK_SH);
        if ($dir = opendir(Db::getFoldername())) {
            while (($entry = readdir($dir)) !== false) {
                if (pathinfo($entry, PATHINFO_EXTENSION) == self::EXT) {
                    $topics[] = self::load(basename($entry, '.' . self::EXT));
                }
            }
        }
        closedir($dir);
        Db::lock(LOCK_UN);
        return $topics;
    }

    /**
     * Loads a topic and returns it.
     *
     * @param string $name A topicname.
     *
     * @return Topic
     */
    protected static function load($name)
    {
        return new self($name);
    }
}
