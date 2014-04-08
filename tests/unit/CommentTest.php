<?php

/**
 * @version SVN: $Id$
 */

require_once './classes/Domain.php';

class CommentTest extends PHPUnit_Framework_TestCase
{
    const ID = 333;

    const TIMESTAMP = 12345;

    const USER = 'cmb';

    const MESSAGE = 'lorem ipsum';

    /**
     * @var Twocents_Comment
     */
    private $_subject;

    public function setUp()
    {
        $this->_topic = $this->getMockBuilder('Twocents_Topic')
            ->disableOriginalConstructor()->getMock();
        $this->_subject = new Twocents_Comment(
            self::ID, $this->_topic, self::TIMESTAMP
        );
    }

    public function testNewCommentHasGivenId()
    {
        $this->assertEquals(self::ID, $this->_subject->getId());
    }

    public function testNewCommentHasGivenTopic()
    {
        $this->assertSame($this->_topic, $this->_subject->getTopic());
    }

    public function testNewCommentHasGivenTimestamp()
    {
        $this->assertEquals(self::TIMESTAMP, $this->_subject->getTimestamp());
    }

    public function testUserIsProperlySet()
    {
        $this->_subject->setUser(self::USER);
        $this->assertEquals(self::USER, $this->_subject->getUser());
    }

    public function testMessageIsProperlySet()
    {
        $this->_subject->setMessage(self::MESSAGE);
        $this->assertEquals(self::MESSAGE, $this->_subject->getMessage());
    }
}


?>
