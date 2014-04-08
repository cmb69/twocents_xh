<?php

/**
 * The domain layer.
 *
 * PHP version 5
 *
 * @category  CMSimple_XH
 * @package   Twocents
 * @author    Christoph M. Becker <cmbecker69@gmx.de>
 * @copyright 2014 Christoph M. Becker <http://3-magi.net>
 * @license   http://www.gnu.org/licenses/gpl-3.0.en.html GNU GPLv3
 * @version   SVN: $Id$
 * @link      http://3-magi.net/?CMSimple_XH/Twocents_XH
 */

/**
 * The model root class.
 *
 * @category CMSimple_XH
 * @package  Twocents
 * @author   Christoph M. Becker <cmbecker69@gmx.de>
 * @license  http://www.gnu.org/licenses/gpl-3.0.en.html GNU GPLv3
 * @link     http://3-magi.net/?CMSimple_XH/Twocents_XH
 */
class Twocents_Model
{
    /**
     * An array of topics.
     *
     * @var array<string, Twocents_Topic>
     */
    private $_topics;

    /**
     * The latest comment ID.
     *
     * @var int
     */
    private $_commentId;

    /**
     * Initializes a new instance.
     */
    public function __construct()
    {
        $this->_topics = array();
        $this->_commentId = 0;
    }

    /**
     * Returns an array of topics.
     *
     * @return array
     */
    public function getTopics()
    {
        return $this->_topics;
    }

    /**
     * Returns whether a certain topic exists.
     *
     * @param string $name A topic name.
     *
     * @return bool
     */
    public function hasTopic($name)
    {
        return isset($this->_topics[$name]);
    }

    /**
     * Adds a new topic.
     *
     * @param string $name A topic name.
     *
     * @return void
     *
     * @throws InvalidArgumentException
     */
    public function addTopic($name)
    {
        if ($this->hasTopic($name)) {
            throw new InvalidArgumentException();
        }
        $this->_topics[$name] = new Twocents_Topic();
    }

    /**
     * Removes a certain topic.
     *
     * @param string $name A topic name.
     *
     * @return void
     */
    public function removeTopic($name)
    {
        unset($this->_topics[$name]);
    }

    /**
     * Adds a new comment.
     *
     * @param string $topicName A topic name.
     * @param string $user      A user name.
     * @param string $message   A message.
     *
     * @return void
     */
    public function addComment($topicName, $user, $message)
    {
        if (!$this->hasTopic($topicName)) {
            $topic = $this->addTopic($topicName);
        }
        $topic = $this->_topics[$topicName];
        $topic->addComment(++$this->_commentId, $user, $message);
    }
}

/**
 * A topic including its comments.
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
     * The comments.
     *
     * @var array<int, Twocents_Comment>
     */
    private $_comments;

    /**
     * Initializes a new instance.
     */
    public function __construct()
    {
        $this->_comments = array();
    }

    /**
     * Returns the comments.
     *
     * @return array
     */
    public function getComments()
    {
        return $this->_comments;
    }

    /**
     * Returns whether a certain comment exists.
     *
     * @param int $id An ID.
     *
     * @return bool
     */
    private function _hasComment($id)
    {
        return isset($this->_comments[$id]);
    }

    /**
     * Adds a new comment.
     *
     * @param int    $id      A comment ID.
     * @param string $user    A user name.
     * @param string $message A message.
     *
     * @return void
     *
     * @throws InvalidArgumentException
     */
    public function addComment($id, $user, $message)
    {
        if ($this->_hasComment($id)) {
            throw new InvalidArgumentException();
        }
        $this->_comments[$id] = new Twocents_Comment(time(), $user, $message);
    }

    /**
     * Removes a comment.
     *
     * @param string $id A comment ID.
     *
     * @return void
     */
    public function removeComment($id)
    {
        unset($this->_comments[$id]);
    }
}

/**
 * A comment.
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
     * The UNIX timestamp.
     *
     * @var int
     */
    private $_timestamp;

    /**
     * The user name.
     *
     * @var string
     */
    private $_user;

    /**
     * The message.
     *
     * @var string
     */
    private $_message;

    /**
     * Initializes a new instance.
     *
     * @param int    $timestamp A UNIX timestamp.
     * @param string $user      A user name.
     * @param string $message   A message.
     */
    public function __construct($timestamp, $user, $message)
    {
        $this->_timestamp = (int) $timestamp;
        $this->_user = $user;
        $this->_message = $message;
    }

    /**
     * Returns the timestamp.
     *
     * @return int
     */
    public function getTimestamp()
    {
        return $this->_timestamp;
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
     * Returns the message.
     *
     * @return string
     */
    public function getMessage()
    {
        return $this->_message;
    }
}

?>
