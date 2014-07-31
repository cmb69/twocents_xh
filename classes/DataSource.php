<?php

/**
 * The data source layer.
 *
 * PHP version 5
 *
 * @category  CMSimple_XH
 * @package   Twocents
 * @author    Christoph M. Becker <cmbecker69@gmx.de>
 * @copyright 2014 Christoph M. Becker <http://3-magi.net/>
 * @license   http://www.gnu.org/licenses/gpl-3.0.en.html GNU GPLv3
 * @version   SVN: $Id$
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
        if (!file_exists($foldername . '.lock')) {
            touch($foldername . '.lock');
        }
        return $foldername;
    }

}

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
     * Returns all topics.
     *
     * @return array
     */
    public static function findAll()
    {
        $topics = array();
        if ($dir = opendir(Twocents_Db::getFoldername())) {
            while (($entry = readdir($dir)) !== false) {
                if (pathinfo($entry, PATHINFO_EXTENSION) == 'csv') {
                    $topics[] = self::_load(basename($entry, '.csv'));
                }
            }
        }
        closedir($dir);
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
        if (file_exists(Twocents_Db::getFoldername() . $name . '.csv')) {
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
        touch(Twocents_Db::getFoldername() . $this->_name . '.csv');
    }

    /**
     * Deletes this topic from the data store including all comments.
     *
     * @return void
     */
    public function delete()
    {
        unlink(Twocents_Db::getFoldername() . $this->_name . '.csv');
    }
}

/**
 * The comments.
 *
 * @category CMSimple_XH
 * @package  Twocents
 * @author   Christoph M. Becker <cmbecker69@gmx.de>
 * @license  http://www.gnu.org/licenses/gpl-3.0.en.html GNU GPLv3
 * @link     http://3-magi.net/?CMSimple_XH/Twocents_XH
 */
class Twocents_Comment
{
    /**
     * Finds all comments for a certain topic and returns them.
     *
     * @param string $name A topicname.
     *
     * @return array
     */
    public static function findByTopicname($name)
    {
        $comments = array();
        $filename = Twocents_Db::getFoldername() . $name . '.csv';
        if (is_readable($filename) && ($file = fopen($filename, 'r'))) {
            while (($record = fgetcsv($file)) !== false) {
                $comments[] = self::_load($name, $record);
            }
            fclose($file);
        }
        return $comments;
    }

    /**
     * Finds a comment and returns it; returns <var>null</var> if topic does not
     * exist.
     *
     * @param string $id        A comment ID.
     * @param string $topicname A topicname.
     *
     * @return Twocents_Comment
     */
    public static function find($id, $topicname)
    {
        $comments = self::findByTopicname($topicname);
        foreach ($comments as $comment) {
            if ($comment->getId() == $id) {
                return $comment;
            }
        }
        return null;
    }

    /**
     * Loads a comment and returns it.
     *
     * @param string $topicname A topicname.
     * @param array  $record    A record.
     *
     * @return Twocents_Comment
     */
    private static function _load($topicname, $record)
    {
        $comment = new self($topicname, $record[1]);
        $comment->_id = $record[0];
        $comment->_user = $record[2];
        $comment->_email = $record[3];
        $comment->_message = $record[4];
        return $comment;
    }

    /**
     * The comment ID.
     *
     * @var string
     */
    private $_id;

    /**
     * The topicname.
     *
     * @var string
     */
    private $_topicname;

    /**
     * The timestamp of the original post.
     *
     * @var int
     */
    private $_time;

    /**
     * The name of the poster.
     *
     * @var string
     */
    private $_user;

    /**
     * The email address of the poster.
     *
     * @var string
     */
    private $_email;

    /**
     * The comment message.
     *
     * @var string
     */
    private $_message;

    /**
     * Makes and returns a comment.
     *
     * @param string $topicname A topicname.
     * @param int    $time      A timestamp.
     *
     * @return Twocents_Comment
     */
    public static function make($topicname, $time)
    {
        return new self($topicname, $time);
    }

    /**
     * Initializes a new instance.
     *
     * @param string $topicname A topicname.
     * @param int    $time      A timestamp.
     *
     * @return void
     */
    private function __construct($topicname, $time)
    {
        $this->_topicname = (string) $topicname;
        $this->_time = (int) $time;
    }

    /**
     * Returns the comment ID.
     *
     * @return string
     */
    public function getId()
    {
        return $this->_id;
    }

    /**
     * Returns the topicname.
     *
     * @return string
     */
    public function getTopicname()
    {
        return $this->_topicname;
    }

    /**
     * Returns the timestamp.
     *
     * @return int
     */
    public function getTime()
    {
        return $this->_time;
    }

    /**
     * Returns the user.
     *
     * @return string
     */
    public function getUser()
    {
        return $this->_user;
    }

    /**
     * Returns the email address.
     *
     * @return string
     */
    public function getEmail()
    {
        return $this->_email;
    }

    /**
     * Returns the comment message.
     *
     * @return string
     */
    public function getMessage()
    {
        return $this->_message;
    }

    /**
     * Sets the username.
     *
     * @param string $user A username.
     *
     * @return void
     */
    public function setUser($user)
    {
        $this->_user = (string) $user;
    }

    /**
     * Sets the email address.
     *
     * @param string $email An email address.
     *
     * @return void
     */
    public function setEmail($email)
    {
        $this->_email = (string) $email;
    }

    /**
     * Sets the comment message.
     *
     * @param string $message A comment message.
     *
     * @return void
     */
    public function setMessage($message)
    {
        $this->_message = (string) $message;
    }

    /**
     * Inserts this comment to the data store.
     *
     * @return void
     */
    public function insert()
    {
        $this->_id = uniqid();
        $file = fopen(
            Twocents_Db::getFoldername() . $this->_topicname . '.csv', 'a'
        );
        fputcsv($file, $this->_toRecord());
        fclose($file);
    }

    /**
     * Updates this comment in the data store.
     *
     * @return void
     */
    public function update()
    {
        $file = fopen(
            Twocents_Db::getFoldername() . $this->_topicname . '.csv', 'r+'
        );
        $temp = fopen('php://temp', 'w+');
        while (($record = fgetcsv($file)) !== false) {
            if ($record[0] != $this->_id) {
                fputcsv($temp, $record);
            } else {
                fputcsv($temp, $this->_toRecord());
            }
        }
        ftruncate($file, 0);
        rewind($file);
        rewind($temp);
        stream_copy_to_stream($temp, $file);
        fclose($file);
        fclose($temp);
    }

    /**
     * Deletes this comment from the data store.
     *
     * @return void
     */
    public function delete()
    {
        $file = fopen(
            Twocents_Db::getFoldername() . $this->_topicname . '.csv', 'r+'
        );
        $temp = fopen('php://temp', 'w+');
        while (($record = fgetcsv($file)) !== false) {
            if ($record[0] != $this->_id) {
                fputcsv($temp, $record);
            }
        }
        ftruncate($file, 0);
        rewind($file);
        rewind($temp);
        stream_copy_to_stream($temp, $file);
        fclose($file);
        fclose($temp);
    }

    /**
     * Returns this comment as record.
     *
     * @return array
     */
    private function _toRecord()
    {
        return array(
            $this->_id, $this->_time, $this->_user, $this->_email, $this->_message
        );
    }
}

?>
