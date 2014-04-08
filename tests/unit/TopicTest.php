<?php

/**
 * @version SVN: $Id$
 */

require_once './classes/Domain.php';

class TopicTest extends PHPUnit_Framework_TestCase
{
    const COMMENT_ID = 12345;

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
        $this->_subject->removeComment(self::COMMENT_ID);
        $after = $this->_subject->getComments();
        $this->assertEquals($before, $after);
    }

    private function _addFooComment()
    {
        $this->_subject->addComment(self::COMMENT_ID, 'cmb', 'lorem ipsum');
    }
}

?>
