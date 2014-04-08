<?php

require_once './classes/Model.php';

class CommentTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var Twocents_Comment
     */
    private $_subject;

    private $_fooId;

    private $_fooUser;

    private $_fooMessage;

    public function setUp()
    {
        $this->_fooId = 12345;
        $this->_fooUser = 'cmb';
        $this->_fooMessage = 'lorem ipsum';
        $this->_subject = new Twocents_Comment(
            $this->_fooId, $this->_fooUser, $this->_fooMessage
        );
    }

    public function testNewCommentHasGivenTimestamp()
    {
        $this->assertEquals($this->_fooId, $this->_subject->getTimestamp());
    }

    public function testNewCommentHasGivenUser()
    {
        $this->assertEquals($this->_fooUser, $this->_subject->getUser());
    }

    public function testNewCommentHasGivenMessage()
    {
        $this->assertEquals($this->_fooMessage, $this->_subject->getMessage());
    }
}


?>
