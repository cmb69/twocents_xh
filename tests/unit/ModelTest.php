<?php

require_once 'vfsStream/vfsStream.php';
require_once './classes/Model.php';

runkit_function_redefine('ftruncate', '$stream, $pos', '');

class ModelTest extends PHPUnit_Framework_TestCase
{
    private $_filename;

    /**
     * @var Twocents_Model
     */
    private $_subject;

    public function setUp()
    {
        global $pth;

        vfsStreamWrapper::register();
        vfsStreamWrapper::setRoot(new vfsStreamDirectory('test'));
        mkdir(vfsStream::url('test/content'));
        $this->_filename = vfsStream::url('test/content/twocents.dat');
        $pth = array(
            'folder' => array(
                'content' => dirname($this->_filename) . '/'
            )
        );
    }

    public function testNewModelHasNoTopics()
    {
        $this->_subject = Twocents_Model::load();
        $this->assertEmpty($this->_subject->getTopics());
    }

    public function testAddTopicIncreasesTopicCount()
    {
        $this->_subject = Twocents_Model::load();
        $before = count($this->_subject->getTopics());
        $this->_addFooTopic();
        $after = count($this->_subject->getTopics());
        $this->assertEquals(1, $after - $before);
    }

    public function testAddAndRemoveTopic()
    {
        $this->_subject = Twocents_Model::load();
        $before = $this->_subject->getTopics();
        $this->_addFooTopic();
        $this->_subject->removeTopic('foo');
        $after = $this->_subject->getTopics();
        $this->assertEquals($before, $after);
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testAddExistingTopicThrowsException()
    {
        $this->_subject = Twocents_Model::load();
        $this->_addFooTopic();
        $this->_addFooTopic();
    }

    private function _addFooTopic()
    {
        $this->_subject->addTopic('foo');
    }

    public function testAddCommentCreatesGivenTopic()
    {
        $this->_subject = Twocents_Model::load();
        $this->assertFalse($this->_subject->hasTopic('foo'));
        $this->_addFooComment();
        $this->assertTrue($this->_subject->hasTopic('foo'));
    }

    public function testAddCommentTwice()
    {
        $this->_subject = Twocents_Model::load();
        $this->_addFooComment();
        $this->_addFooComment();
    }

    private function _addFooComment()
    {
        $this->_subject->addComment('foo', 'cmb', 'lorem ipsum');
    }

    public function testLoadDefaults()
    {
        $this->_subject = Twocents_Model::load();
        $this->assertFileNotExists($this->_filename);
        $model = Twocents_Model::load();
        $this->assertEquals($this->_subject, $model);
    }

    public function testLoadStored()
    {
        $this->_subject = Twocents_Model::open();
        $this->_addFooTopic();
        $this->_subject->close();
        $this->assertFileExists($this->_filename);
        $model = Twocents_Model::load();
        $this->assertEquals($this->_subject, $model);
    }
}

?>
