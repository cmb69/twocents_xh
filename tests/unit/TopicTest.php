<?php

require_once './classes/Model.php';

class TopicTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var Twocents_Topic
     */
    private $_subject;

    public function setUp()
    {
        $this->_subject = new Twocents_Topic();
    }

    public function testNewTopicHasNoComments()
    {
        $this->assertEmpty($this->_subject->getComments());
    }

    public function testAddCommentIncreasesCommentCount()
    {
        $before = count($this->_subject->getComments());
        $this->_addFooComment();
        $after = count($this->_subject->getComments());
        $this->assertEquals(1, $after - $before);
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testAddExistingCommentThrowsExecption()
    {
        $this->_addFooComment();
        $this->_addFooComment();
    }

    public function testAddAndRemoveComment()
    {
        $before = $this->_subject->getComments();
        $this->_addFooComment();
        $this->_subject->removeComment(12345);
        $after = $this->_subject->getComments();
        $this->assertEquals($before, $after);
    }

    private function _addFooComment()
    {
        $this->_subject->addComment(12345, 'cmb', 'lorem ipsum');
    }
}

?>
