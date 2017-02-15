<?php

/**
 * The topics.
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
 * The topics.
 *
 * @category CMSimple_XH
 * @package  Twocents
 * @author   Christoph M. Becker <cmbecker69@gmx.de>
 * @license  http://www.gnu.org/licenses/gpl-3.0.en.html GNU GPLv3
 * @link     http://3-magi.net/?CMSimple_XH/Twocents_XH
 */
class Topic
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
     * Finds a topic by name and returns it; returns <var>null</var> if topic
     * does not exist.
     *
     * @param string $name A topicname.
     *
     * @return Topic
     */
    public static function findByName($name)
    {
        if (file_exists(Db::getFoldername() . $name . '.' . self::EXT)) {
            return self::load($name);
        } else {
            return null;
        }
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

    /**
     * The topicname.
     *
     * @var string
     */
    protected $name;

    /**
     * Initializes a new instance.
     *
     * @param string $name A topicname.
     *
     * @return void
     */
    public function __construct($name)
    {
        $this->name = (string) $name;
    }

    /**
     * Returns the topicname.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Inserts this topic to the data store.
     *
     * @return void
     */
    public function insert()
    {
        Db::lock(LOCK_EX);
        touch(Db::getFoldername() . $this->name . '.' . self::EXT);
        Db::lock(LOCK_UN);
    }

    /**
     * Deletes this topic from the data store including all comments.
     *
     * @return void
     */
    public function delete()
    {
        Db::lock(LOCK_EX);
        unlink(Db::getFoldername() . $this->name . '.' . self::EXT);
        Db::lock(LOCK_UN);
    }
}

?>
