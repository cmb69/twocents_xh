<?php

/**
 * The data source layer.
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
 * Save and load model objects from a file.
 *
 * @category CMSimple_XH
 * @package  Twocents
 * @author   Christoph M. Becker <cmbecker69@gmx.de>
 * @license  http://www.gnu.org/licenses/gpl-3.0.en.html GNU GPLv3
 * @link     http://3-magi.net/?CMSimple_XH/Twocents_XH
 */
class Twocents_Persister
{
    /**
     * The filename.
     *
     * @var string
     */
    private $_filename;

    /**
     * The retrieved model object.
     *
     * @var Twocents_Model
     */
    private $_subject;

    /**
     * The stream for later writing the model object to.
     *
     * @var resource
     */
    private $_stream;

    /**
     * Initializes a new instance.
     *
     * @param string $filename A filename.
     */
    public function __construct($filename)
    {
        $this->_filename = $filename;
    }

    /**
     * Loads and returns a model object.
     *
     * @return Twocents_Model
     */
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
     * Reads the remaining contents of a stream and closes the stream.
     *
     * @param resource $stream A stream.
     *
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

    /**
     * Loads and returns a model object.
     *
     * Keeps a lock on the stream.
     *
     * @return Twocents_Model
     */
    public function open()
    {
        $this->_stream = fopen($this->_filename, 'a+');
        flock($this->_stream, LOCK_EX);
        $contents = stream_get_contents($this->_stream);
        $this->_subject = unserialize($contents);
        if (!$this->_subject) {
            $this->_subject = new Twocents_Model();
        }
        return $this->_subject;
    }

    /**
     * Saves the model object and closes the stream.
     *
     * @return void
     */
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
