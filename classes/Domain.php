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
     * Returns the latest comments.
     *
     * @return array
     */
    public function getLatestComments()
    {
        $result = array();
        foreach ($this->_topics as $topic) {
            $result += $topic->getComments();
        }
        usort($result, array('Twocents_Comment', 'compare'));
        return $result;
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
        $this->_topics[$name] = new Twocents_Topic($name);
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
     * @param int    $timestamp A UNIX timestamp.
     * @param string $user      A user name.
     * @param string $message   A message.
     *
     * @return void
     */
    public function addComment($topicName, $timestamp, $user, $message)
    {
        if (!$this->hasTopic($topicName)) {
            $topic = $this->addTopic($topicName);
        }
        $topic = $this->_topics[$topicName];
        $topic->addComment(++$this->_commentId, $timestamp, $user, $message);
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
     * The topic name.
     *
     * @var string
     */
    private $_name;

    /**
     * The comments.
     *
     * @var array<int, Twocents_Comment>
     */
    private $_comments;

    /**
     * Initializes a new instance.
     *
     * @param string $name A topic name.
     */
    public function __construct($name)
    {
        $this->_name = $name;
        $this->_comments = array();
    }

    /**
     * Returns the topic name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->_name;
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
     * @param int    $id        A comment ID.
     * @param int    $timestamp A UNIX timestamp.
     * @param string $user      A user name.
     * @param string $message   A message.
     *
     * @return void
     *
     * @throws InvalidArgumentException
     */
    public function addComment($id, $timestamp, $user, $message)
    {
        if ($this->_hasComment($id)) {
            throw new InvalidArgumentException();
        }
        $comment = new Twocents_Comment($id, $this, $timestamp);
        $comment->setUser($user);
        $comment->setMessage($message);
        $this->_comments[$id] = $comment;
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
     * The ID.
     *
     * @var int
     */
    private $_id;

    /**
     * The topic.
     *
     * @var Twocents_Topic
     */
    private $_topic;

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
     * Compares the timestamps of comments.
     *
     * @param Twocents_Comment $a A comment.
     * @param Twocents_Comment $b Another comment.
     *
     * @return int
     */
    public static function compare(Twocents_Comment $a, Twocents_Comment $b)
    {
        return $b->_timestamp - $a->_timestamp;
    }

    /**
     * Initializes a new instance.
     *
     * @param int            $id        An ID.
     * @param Twocents_Topic $topic     A topic.
     * @param int            $timestamp A UNIX timestamp.
     */
    public function __construct($id, Twocents_Topic $topic, $timestamp)
    {
        $this->_id = $id;
        $this->_topic = $topic;
        $this->_timestamp = (int) $timestamp;
    }

    /**
     * Returns the ID.
     *
     * @return int
     */
    public function getId()
    {
        return $this->_id;
    }

    /**
     * Returns the topic.
     *
     * @return Twocents_Topic
     */
    public function getTopic()
    {
        return $this->_topic;
    }

    /**
     * Returns the name of the topic.
     *
     * @return string
     */
    public function getTopicName()
    {
        return $this->_topic->getName();
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
     * Returns the user name.
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

    /**
     * Sets the user name.
     *
     * @param string $user A user name.
     *
     * @return void
     */
    public function setUser($user)
    {
        $this->_user = $user;
    }

    /**
     * Sets the message.
     *
     * @param string $message A message.
     *
     * @return void
     */
    public function setMessage($message)
    {
        $this->_message = $message;
    }
}

?>
