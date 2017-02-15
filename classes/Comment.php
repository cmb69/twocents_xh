<?php

/**
 * The comments.
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
 * The comments.
 *
 * @category CMSimple_XH
 * @package  Twocents
 * @author   Christoph M. Becker <cmbecker69@gmx.de>
 * @license  http://www.gnu.org/licenses/gpl-3.0.en.html GNU GPLv3
 * @link     http://3-magi.net/?CMSimple_XH/Twocents_XH
 */
class Comment
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
        Db::lock(LOCK_SH);
        $filename = Db::getFoldername() . $name . '.' . self::EXT;
        if (is_readable($filename) && ($file = fopen($filename, 'r'))) {
            while (($record = fgetcsv($file)) !== false) {
                $comments[] = self::load($name, $record);
            }
            fclose($file);
        }
        Db::lock(LOCK_UN);
        return $comments;
    }

    /**
     * Finds a comment and returns it; returns <var>null</var> if topic does not
     * exist.
     *
     * @param string $id        A comment ID.
     * @param string $topicname A topicname.
     *
     * @return Comment
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
     * @return Comment
     */
    protected static function load($topicname, $record)
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
     * @return Comment
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
        Db::lock(LOCK_EX);
        $file = fopen(
            Db::getFoldername() . $this->topicname . '.' . self::EXT, 'a'
        );
        fputcsv($file, $this->toRecord());
        fclose($file);
        Db::lock(LOCK_UN);
    }

    /**
     * Updates this comment in the data store.
     *
     * @return void
     */
    public function update()
    {
        Db::lock(LOCK_EX);
        $file = fopen(
            Db::getFoldername() . $this->topicname . '.' . self::EXT, 'r+'
        );
        $temp = fopen('php://temp', 'w+');
        while (($record = fgetcsv($file)) !== false) {
            if ($record[0] != $this->id) {
                fputcsv($temp, $record);
            } else {
                fputcsv($temp, $this->toRecord());
            }
        }
        ftruncate($file, 0);
        rewind($file);
        rewind($temp);
        stream_copy_to_stream($temp, $file);
        fclose($file);
        fclose($temp);
        Db::lock(LOCK_UN);
    }

    /**
     * Deletes this comment from the data store.
     *
     * @return void
     */
    public function delete()
    {
        Db::lock(LOCK_EX);
        $file = fopen(
            Db::getFoldername() . $this->topicname . '.' . self::EXT, 'r+'
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
        Db::lock(LOCK_UN);
    }

    /**
     * Returns this comment as record.
     *
     * @return array
     */
    protected function toRecord()
    {
        return array(
            $this->id, $this->time, $this->user, $this->email,
            $this->message, $this->hidden
        );
    }
}

?>
