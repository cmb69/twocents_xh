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
     * The lock file handle.
     *
     * @var resource
     */
    private static $_lockFile;

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
            self::$_lockFile = fopen(self::_getLockFilename(), 'r');
            flock(self::$_lockFile, $operation);
            break;
        case LOCK_UN:
            flock(self::$_lockFile, $operation);
            fclose(self::$_lockFile);
            break;
        }
    }

    /**
     * Returns the path of the lock file.
     *
     * @return string
     */
    private static function _getLockFilename()
    {
        return self::getFoldername() . '.lock';
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
     * The file extension.
     */
    const EXT = 'csv';

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
        Twocents_Db::lock(LOCK_SH);
        $filename = Twocents_Db::getFoldername() . $name . '.' . self::EXT;
        if (is_readable($filename) && ($file = fopen($filename, 'r'))) {
            while (($record = fgetcsv($file)) !== false) {
                $comments[] = self::_load($name, $record);
            }
            fclose($file);
        }
        Twocents_Db::lock(LOCK_UN);
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
        $comment->id = $record[0];
        $comment->user = $record[2];
        $comment->email = $record[3];
        $comment->message = $record[4];
        $comment->hidden = isset($record[5]) ? (bool) $record[5] : false;
        return $comment;
    }

    /**
     * The comment ID.
     *
     * @var string
     */
    protected $id;

    /**
     * The topicname.
     *
     * @var string
     */
    protected $topicname;

    /**
     * The timestamp of the original post.
     *
     * @var int
     */
    protected $time;

    /**
     * The name of the poster.
     *
     * @var string
     */
    protected $user;

    /**
     * The email address of the poster.
     *
     * @var string
     */
    protected $email;

    /**
     * The comment message.
     *
     * @var string
     */
    protected $message;

    /**
     * Whether the comment is hidden.
     *
     * @var bool
     */
    protected $hidden;

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
    protected function __construct($topicname, $time)
    {
        $this->topicname = (string) $topicname;
        $this->time = (int) $time;
        $this->hidden = false;
    }

    /**
     * Returns the comment ID.
     *
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Returns the topicname.
     *
     * @return string
     */
    public function getTopicname()
    {
        return $this->topicname;
    }

    /**
     * Returns the timestamp.
     *
     * @return int
     */
    public function getTime()
    {
        return $this->time;
    }

    /**
     * Returns the user.
     *
     * @return string
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Returns the email address.
     *
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Returns the comment message.
     *
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * Returns whether the comment is visible.
     *
     * @return bool
     */
    public function isVisible()
    {
        return !$this->hidden;
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
        $this->user = (string) $user;
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
        $this->email = (string) $email;
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
        $this->message = (string) $message;
    }

    /**
     * Hides this comment.
     *
     * @return void
     */
    public function hide()
    {
        $this->hidden = true;
    }

    /**
     * Shows this comment.
     *
     * @return void
     */
    public function show()
    {
        $this->hidden = false;
    }

    /**
     * Inserts this comment to the data store.
     *
     * @return void
     */
    public function insert()
    {
        $this->id = uniqid();
        Twocents_Db::lock(LOCK_EX);
        $file = fopen(
            Twocents_Db::getFoldername() . $this->topicname . '.' . self::EXT, 'a'
        );
        fputcsv($file, $this->_toRecord());
        fclose($file);
        Twocents_Db::lock(LOCK_UN);
    }

    /**
     * Updates this comment in the data store.
     *
     * @return void
     */
    public function update()
    {
        Twocents_Db::lock(LOCK_EX);
        $file = fopen(
            Twocents_Db::getFoldername() . $this->topicname . '.' . self::EXT, 'r+'
        );
        $temp = fopen('php://temp', 'w+');
        while (($record = fgetcsv($file)) !== false) {
            if ($record[0] != $this->id) {
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
        Twocents_Db::lock(LOCK_UN);
    }

    /**
     * Deletes this comment from the data store.
     *
     * @return void
     */
    public function delete()
    {
        Twocents_Db::lock(LOCK_EX);
        $file = fopen(
            Twocents_Db::getFoldername() . $this->topicname . '.' . self::EXT, 'r+'
        );
        $temp = fopen('php://temp', 'w+');
        while (($record = fgetcsv($file)) !== false) {
            if ($record[0] != $this->id) {
                fputcsv($temp, $record);
            }
        }
        ftruncate($file, 0);
        rewind($file);
        rewind($temp);
        stream_copy_to_stream($temp, $file);
        fclose($file);
        fclose($temp);
        Twocents_Db::lock(LOCK_UN);
    }

    /**
     * Returns this comment as record.
     *
     * @return array
     */
    private function _toRecord()
    {
        return array(
            $this->id, $this->time, $this->user, $this->email,
            $this->message, $this->hidden
        );
    }
}

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

/**
 * The comments of the Comments plugin.
 *
 * @category CMSimple_XH
 * @package  Twocents
 * @author   Christoph M. Becker <cmbecker69@gmx.de>
 * @license  http://www.gnu.org/licenses/gpl-3.0.en.html GNU GPLv3
 * @link     http://3-magi.net/?CMSimple_XH/Twocents_XH
 */
class Twocents_CommentsComment extends Twocents_Comment
{
    /**
     * The file extension.
     */
    const EXT = 'txt';

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
        Twocents_Db::lock(LOCK_SH);
        $filename = Twocents_Db::getFoldername() . $name . '.' . self::EXT;
        if (is_readable($filename) && ($file = fopen($filename, 'r'))) {
            if (fgets($file) !== false) {
                while (($line = fgets($file)) !== false) {
                    $record = explode('-,+;-', trim($line));
                    $comments[] = self::_load($name, $record);
                }
            }
            fclose($file);
        }
        Twocents_Db::lock(LOCK_UN);
        return $comments;
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
        // image is $record[6]
        $comment = new parent($topicname, $record[5]);
        $comment->user = $record[1];
        $comment->email = $record[2];
        $comment->message = $record[7];
        $comment->hidden = $record[5] == 'hidden';
        return $comment;
    }
}



?>
