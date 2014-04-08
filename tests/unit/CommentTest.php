<?php

/**
 * @version SVN: $Id$
 */

require_once './classes/Domain.php';

class CommentTest extends PHPUnit_Framework_TestCase
{
    const ID = 12345;

    const USER = 'cmb';

    const MESSAGE = 'lorem ipsum';

    /**
     * @var Twocents_Comment
     */
    private $_subject;

    public function setUp()
    {
        $this->_subject = new Twocents_Comment(
            self::ID, self::USER, self::MESSAGE
        );
    }

    public function testNewCommentHasGivenTimestamp()
    {
        $this->assertEquals(self::ID, $this->_subject->getTimestamp());
    }

    public function testNewCommentHasGivenUser()
    {
        $this->assertEquals(self::USER, $this->_subject->getUser());
    }

    public function testNewCommentHasGivenMessage()
    {
        $this->assertEquals(self::MESSAGE, $this->_subject->getMessage());
    }
}


?>
