<?php

class Twocents_Model
{
    private $_filename;

    private $_stream;

    /**
     * @var array<string, Twocents_Topic>
     */
    private $_topics;

    private $_commentId;

    public static function load()
    {
        global $pth;

        $filename = $pth['folder']['content'] . 'twocents.dat';
        if ($result = self::_load($filename)) {
            return $result;
        } else {
            return new Twocents_Model($filename);
        }
    }

    private static function _load($filename)
    {
        if (!is_readable($filename)) {
            return false;
        }
        $stream = fopen($filename, 'r');
        flock($stream, LOCK_SH);
        $contents = stream_get_contents($stream);
        flock($stream, LOCK_UN);
        fclose($stream);
        return unserialize($contents);
    }

    public static function open()
    {
        global $pth;

        $filename = $pth['folder']['content'] . 'twocents.dat';
        $stream = fopen($filename, 'a+');
        flock($stream, LOCK_EX);
        $contents = stream_get_contents($stream);
        $result = unserialize($contents);
        if (!$result) {
            $result = new Twocents_Model($filename);
        }
        $result->_stream = $stream;
        return $result;
    }

    private function __construct($filename)
    {
        $this->_filename = $filename;
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

    public function close()
    {
        //file_put_contents($this->_filename, serialize($this));

        fseek($this->_stream, 0);
        $bytes = fwrite($this->_stream, serialize($this));
        ftruncate($this->_stream, $bytes);
        flock($this->_stream, LOCK_UN);
        fclose($this->_stream);
        $this->_stream = null;
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
