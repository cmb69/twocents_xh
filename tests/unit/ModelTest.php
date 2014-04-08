<?php

/**
 * @version SVN: $Id$
 */

require_once './classes/Domain.php';

class ModelTest extends PHPUnit_Framework_TestCase
{
    const TOPIC_NAME = 'foo';

    /**
     * @var Twocents_Model
     */
    private $_subject;

    public function setUp()
    {
        $this->_subject = new Twocents_Model();
    }

    public function testNewModelHasNoTopics()
    {
        $this->assertEmpty($this->_subject->getTopics());
    }

    public function testAddTopicIncreasesTopicCount()
    {
        $before = count($this->_subject->getTopics());
        $this->_addFooTopic();
        $after = count($this->_subject->getTopics());
        $this->assertEquals(1, $after - $before);
    }

    public function testAddAndRemoveTopic()
    {
        $before = $this->_subject->getTopics();
        $this->_addFooTopic();
        $this->_subject->removeTopic(self::TOPIC_NAME);
        $after = $this->_subject->getTopics();
        $this->assertEquals($before, $after);
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testAddTopicTwiceThrowsException()
    {
        $this->_addFooTopic();
        $this->_addFooTopic();
    }

    private function _addFooTopic()
    {
        $this->_subject->addTopic(self::TOPIC_NAME);
    }

    public function testAddCommentCreatesGivenTopic()
    {
        $this->assertFalse($this->_subject->hasTopic(self::TOPIC_NAME));
        $this->_addFooComment();
        $this->assertTrue($this->_subject->hasTopic(self::TOPIC_NAME));
    }

    public function testAddCommentTwice()
    {
        $this->_addFooComment();
        $this->_addFooComment();
    }

    private function _addFooComment()
    {
        $this->_subject->addComment(self::TOPIC_NAME, 'cmb', 'lorem ipsum');
    }
}

?>
