<?php

/**
 * @version SVN: $Id$
 */

require_once './classes/Domain.php';

class TopicTest extends PHPUnit_Framework_TestCase
{
    const NAME = 'foo';

    const COMMENT_ID = 12345;

    const TIMESTAMP = 333;

    const USER = 'cmb';

    const MESSAGE = 'lorem ipsum';

    /**
     * @var Twocents_Topic
     */
    private $_subject;

    public function setUp()
    {
        $this->_subject = new Twocents_Topic(self::NAME);
    }

    public function testNewTopicHasGivenName()
    {
        $this->assertEquals(self::NAME, $this->_subject->getName());
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

    public function testAddCommentSetUserAndMessage()
    {
        $this->_addFooComment();
        $comments = $this->_subject->getComments();
        $comment = reset($comments);
        $this->assertEquals(self::USER, $comment->getUser());
        $this->assertEquals(self::MESSAGE, $comment->getMessage());
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
        $this->_subject->addComment(
            self::COMMENT_ID, self::TIMESTAMP, self::USER, self::MESSAGE
        );
    }
}

?>
