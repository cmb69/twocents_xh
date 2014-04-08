<?php

/**
 * @version SVN: $Id$
 */

class Twocents_Model
{
    /**
     * @var array<string, Twocents_Topic>
     */
    private $_topics;

    private $_commentId;

    public function __construct()
    {
        $this->_topics = array();
        $this->_commentId = 0;
    }

    public function getTopics()
    {
        return $this->_topics;
    }

    public function hasTopic($name)
    {
        return isset($this->_topics[$name]);
    }

    public function addTopic($name)
    {
        if ($this->hasTopic($name)) {
            throw new InvalidArgumentException();
        }
        $this->_topics[$name] = new Twocents_Topic();
    }

    public function removeTopic($name)
    {
        unset($this->_topics[$name]);
    }

    public function addComment($topicName, $user, $message)
    {
        if (!$this->hasTopic($topicName)) {
            $topic = $this->addTopic($topicName);
        }
        $topic = $this->_topics[$topicName];
        $topic->addComment(++$this->_commentId, $user, $message);
    }
}

class Twocents_Topic
{
    /**
     * @var array<int, Twocents_Comment>
     */
    private $_comments;

    public function __construct()
    {
        $this->_comments = array();
    }

    public function getComments()
    {
        return $this->_comments;
    }

    private function _hasComment($id)
    {
        return isset($this->_comments[$id]);
    }

    public function addComment($id, $user, $message)
    {
        if ($this->_hasComment($id)) {
            throw new InvalidArgumentException();
        }
        $this->_comments[$id] = new Twocents_Comment(time(), $user, $message);
    }

    public function removeComment($id)
    {
        unset($this->_comments[$id]);
    }
}

class Twocents_Comment
{
    /**
     * @var int
     */
    private $_timestamp;

    /**
     * @var string
     */
    private $_user;

    /**
     * @var string
     */
    private $_message;

    public function __construct($timestamp, $user, $message)
    {
        $this->_timestamp = (int) $timestamp;
        $this->_user = $user;
        $this->_message = $message;
    }

    public function getTimestamp()
    {
        return $this->_timestamp;
    }

    public function getUser()
    {
        return $this->_user;
    }

    public function getMessage()
    {
        return $this->_message;
    }
}

?>
