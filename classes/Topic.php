<?php

/**
 * The topics.
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
 * The topics.
 *
 * @category CMSimple_XH
 * @package  Twocents
 * @author   Christoph M. Becker <cmbecker69@gmx.de>
 * @license  http://www.gnu.org/licenses/gpl-3.0.en.html GNU GPLv3
 * @link     http://3-magi.net/?CMSimple_XH/Twocents_XH
 */
class Twocents_Topic
{
    /**
     * The file extension.
     */
    const EXT = 'csv';

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
     * Finds a topic by name and returns it; returns <var>null</var> if topic
     * does not exist.
     *
     * @param string $name A topicname.
     *
     * @return Twocents_Topic
     */
    public static function findByName($name)
    {
        if (file_exists(Twocents_Db::getFoldername() . $name . '.' . self::EXT)) {
            return self::_load($name);
        } else {
            return null;
        }
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

    /**
     * The topicname.
     *
     * @var string
     */
    private $_name;

    /**
     * Initializes a new instance.
     *
     * @param string $name A topicname.
     *
     * @return void
     */
    public function __construct($name)
    {
        $this->_name = (string) $name;
    }

    /**
     * Returns the topicname.
     *
     * @return string
     */
    public function getName()
    {
        return $this->_name;
    }

    /**
     * Inserts this topic to the data store.
     *
     * @return void
     */
    public function insert()
    {
        Twocents_Db::lock(LOCK_EX);
        touch(Twocents_Db::getFoldername() . $this->_name . '.' . self::EXT);
        Twocents_Db::lock(LOCK_UN);
    }

    /**
     * Deletes this topic from the data store including all comments.
     *
     * @return void
     */
    public function delete()
    {
        Twocents_Db::lock(LOCK_EX);
        unlink(Twocents_Db::getFoldername() . $this->_name . '.' . self::EXT);
        Twocents_Db::lock(LOCK_UN);
    }
}

?>
