<?php

/**
 * @version SVN: $Id$
 */

class Twocents_Persister
{
    /**
     * @var string
     */
    private $_filename;

    /**
     * @var Twocents_Model
     */
    private $_subject;

    private $_stream;

    public function __construct($filename)
    {
        $this->_filename = $filename;
    }

    public function load()
    {
        if (!is_readable($this->_filename)) {
            return new Twocents_Model();
        }
        $stream = fopen($this->_filename, 'r');
        $contents = $this->_readFromStream($stream);
        $subject = unserialize($contents);
        if ($subject) {
            return $subject;
        } else {
            return new Twocents_Model();
        }
    }

    /**
     * @return string
     */
    private function _readFromStream($stream)
    {
        flock($stream, LOCK_SH);
        $contents = stream_get_contents($stream);
        flock($stream, LOCK_UN);
        fclose($stream);
        return $contents;
    }

    public function open()
    {
        $this->_stream = fopen($this->_filename, 'a+');
        //if ($this->_stream === false) {
        //    throw new RuntimeException();
        //}
        flock($this->_stream, LOCK_EX);
        $contents = stream_get_contents($this->_stream);
        $this->_subject = unserialize($contents);
        if (!$this->_subject) {
            $this->_subject = new Twocents_Model();
        }
        return $this->_subject;
    }

    public function close()
    {
        fseek($this->_stream, 0);
        $bytes = fwrite($this->_stream, serialize($this->_subject));
        ftruncate($this->_stream, $bytes);
        flock($this->_stream, LOCK_UN);
        fclose($this->_stream);
        $this->_subject = null;
        $this->_stream = null;
    }
}


?>
