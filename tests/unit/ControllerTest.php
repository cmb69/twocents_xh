<?php

/**
 * Testing the controllers.
 *
 * PHP version 5
 *
 * @category  Testing
 * @package   Twocents
 * @author    Christoph M. Becker <cmbecker69@gmx.de>
 * @copyright 2014 Christoph M. Becker <http://3-magi.net/>
 * @license   http://www.gnu.org/licenses/gpl-3.0.en.html GNU GPLv3
 * @version   SVN: $Id$
 * @link      http://3-magi.net/?CMSimple_XH/Twocents_XH
 */

require_once '../../cmsimple/functions.php';
require_once './vendor/autoload.php';
require_once './classes/DataSource.php';
require_once './classes/Presentation.php';

/**
 * Testing the controllers.
 *
 * @category CMSimple_XH
 * @package  Twocents
 * @author   Christoph M. Becker <cmbecker69@gmx.de>
 * @license  http://www.gnu.org/licenses/gpl-3.0.en.html GNU GPLv3
 * @link     http://3-magi.net/?CMSimple_XH/Twocents_XH
 */
class ControllerTest extends PHPUnit_Framework_TestCase
{
    /**
     * The test subject.
     *
     * @var Twocents_Controller
     */
    private $_subject;

    /**
     * The findByTopicname mock.
     *
     * @var object
     */
    private $_findByTopicnameMock;

    /**
     * Sets up the test fixture.
     *
     * @return void
     */
    public function setUp()
    {
        $this->_defineConstant('XH_ADM', false);
        $this->_subject = new Twocents_Controller();
        $this->_findByTopicnameMock = new PHPUnit_Extensions_MockStaticMethod(
            'Twocents_Comment::findByTopicname', $this->_subject
        );
        $viewSpy = $this->getMockBuilder('Twocents_CommentsView')
            ->disableOriginalConstructor()->getMock();
        $viewSpy->expects($this->once())->method('render');
        $viewMakeStub = new PHPUnit_Extensions_MockStaticMethod(
            'Twocents_CommentsView::make', $this->_subject
        );
        $viewMakeStub->expects($this->any())->will($this->returnValue($viewSpy));
    }

    /**
     * Tests that the comments are rendered.
     *
     * @return void
     */
    public function testRenderComments()
    {
        $this->_findByTopicnameMock->expects($this->once())->with('foo');
        $this->_subject->renderComments('foo');
    }

    /**
     * Tests that a comment is added.
     *
     * @return void
     */
    public function testAddComment()
    {
        $_POST = array(
            'twocents_action' => 'add_comment',
            'twocents_user' => 'cmb',
            'twocents_email' => 'me@example.com',
            'twocents_message' => 'blah blah'
        );
        $_SERVER['QUERY_STRING'] = 'Page';
        $commentSpy = $this->getMockBuilder('Twocents_Comment')
            ->disableOriginalConstructor()
            ->setMethods(array('insert'))
            ->getMock();
        $commentSpy->expects($this->once())->method('insert');
        $makeMock = new PHPUnit_Extensions_MockStaticMethod(
            'Twocents_Comment::make', $this->_subject
        );
        $makeMock->expects($this->once())->will($this->returnValue($commentSpy));
        $this->_subject->renderComments('foo');
    }

    /**
     * Tests that a comment with an invalid username is not added.
     *
     * @return void
     */
    public function testDoesNotAddInvalidUserComment()
    {
        $_POST = array(
            'twocents_action' => 'add_comment',
            'twocents_user' => '',
            'twocents_email' => 'me@example.com',
            'twocents_message' => 'blah blah'
        );
        $this->_assertDoesNotAddInvalidComment();
    }

    /**
     * Tests that a comment with an invalid email address is not added.
     *
     * @return void
     */
    public function testDoesNotAddInvalidEmailComment()
    {
        $_POST = array(
            'twocents_action' => 'add_comment',
            'twocents_user' => 'cmb',
            'twocents_email' => '',
            'twocents_message' => 'blah blah'
        );
        $this->_assertDoesNotAddInvalidComment();
    }

    /**
     * Tests that a comment with an invalid comment message is not added.
     *
     * @return void
     */
    public function testDoesNotAddInvalidMessageComment()
    {
        $_POST = array(
            'twocents_action' => 'add_comment',
            'twocents_user' => 'cmb',
            'twocents_email' => 'me@example.com',
            'twocents_message' => ''
        );
        $this->_assertDoesNotAddInvalidComment();
    }

    /**
     * Asserts that an invalid comment is not added.
     *
     * @return void
     */
    private function _assertDoesNotAddInvalidComment()
    {
        $_SERVER['QUERY_STRING'] = 'Page';
        $commentSpy = $this->getMockBuilder('Twocents_Comment')
            ->disableOriginalConstructor()
            ->setMethods(array('insert'))
            ->getMock();
        $commentSpy->expects($this->never())->method('insert');
        $makeMock = new PHPUnit_Extensions_MockStaticMethod(
            'Twocents_Comment::make', $this->_subject
        );
        $makeMock->expects($this->once())->will($this->returnValue($commentSpy));
        $this->_subject->renderComments('foo');
    }

    /**
     * Tests that a comment is updated.
     *
     * @return void
     */
    public function testUpdateComment()
    {
        $this->_defineConstant('XH_ADM', true);
        $_POST = array(
            'twocents_action' => 'update_comment',
            'twocents_id' => '1a2b3c',
            'twocents_user' => 'cmb',
            'twocents_email' => 'me@example.com',
            'twocents_message' => 'blah blah'
        );
        $_SERVER['QUERY_STRING'] = 'Page';
        $commentSpy = $this->getMockBuilder('Twocents_Comment')
            ->disableOriginalConstructor()
            ->setMethods(array('update'))
            ->getMock();
        $commentSpy->expects($this->once())->method('update');
        $makeMock = new PHPUnit_Extensions_MockStaticMethod(
            'Twocents_Comment::find', $this->_subject
        );
        $makeMock->expects($this->once())->will($this->returnValue($commentSpy));
        $this->_subject->renderComments('foo');
    }

    /**
     * Tests that a comment with an invalid username is not updated.
     *
     * @return void
     */
    public function testDoesNotUpdateInvalidUserComment()
    {
        $this->_defineConstant('XH_ADM', true);
        $_POST = array(
            'twocents_action' => 'update_comment',
            'twocents_id' => '1a2b3c',
            'twocents_user' => '',
            'twocents_email' => 'me@example.com',
            'twocents_message' => 'blah blah'
        );
        $this->_assertDoesNotUpdateInvalidComment();
    }

    /**
     * Tests that a comment with an invalid email address is not updated.
     *
     * @return void
     */
    public function testDoesNotUpdateInvalidEmailComment()
    {
        $this->_defineConstant('XH_ADM', true);
        $_POST = array(
            'twocents_action' => 'update_comment',
            'twocents_id' => '1a2b3c',
            'twocents_user' => 'cmb',
            'twocents_email' => '',
            'twocents_message' => 'blah blah'
        );
        $this->_assertDoesNotUpdateInvalidComment();
    }

    /**
     * Tests that a comment with an invalid comment message is not updated.
     *
     * @return void
     */
    public function testDoesNotUpdateInvalidMessageComment()
    {
        $this->_defineConstant('XH_ADM', true);
        $_POST = array(
            'twocents_action' => 'update_comment',
            'twocents_id' => '1a2b3c',
            'twocents_user' => 'cmb',
            'twocents_email' => 'me@example.com',
            'twocents_message' => ''
        );
        $this->_assertDoesNotUpdateInvalidComment();
    }

    /**
     * Asserts that an invalid comment is not updated.
     *
     * @return void
     */
    private function _assertDoesNotUpdateInvalidComment()
    {
        $_SERVER['QUERY_STRING'] = 'Page';
        $commentSpy = $this->getMockBuilder('Twocents_Comment')
            ->disableOriginalConstructor()
            ->setMethods(array('update'))
            ->getMock();
        $commentSpy->expects($this->never())->method('update');
        $makeMock = new PHPUnit_Extensions_MockStaticMethod(
            'Twocents_Comment::find', $this->_subject
        );
        $makeMock->expects($this->once())->will($this->returnValue($commentSpy));
        $this->_subject->renderComments('foo');
    }

    /**
     * Tests that a comment is not updated from the front-end.
     *
     * @return void
     */
    public function testDoesNotUpdateCommentFromFrontEnd()
    {
        $_POST = array(
            'twocents_action' => 'update_comment',
            'twocents_id' => '1a2b3c',
            'twocents_user' => 'cmb',
            'twocents_email' => 'me@example.com',
            'twocents_message' => 'blah blah'
        );
        $_SERVER['QUERY_STRING'] = 'Page';
        $makeMock = new PHPUnit_Extensions_MockStaticMethod(
            'Twocents_Comment::find', $this->_subject
        );
        $makeMock->expects($this->never());
        $this->_subject->renderComments('foo');
    }

    /**
     * Tests that a comment is deleted.
     *
     * @return void
     */
    public function testDeleteComment()
    {
        $this->_defineConstant('XH_ADM', true);
        $_POST = array(
            'twocents_action' => 'remove_comment',
            'twocents_id' => '1a2b3c'
        );
        $commentSpy = $this->getMockBuilder('Twocents_Comment')
            ->disableOriginalConstructor()->getMock();
        $commentSpy->expects($this->once())->method('delete');
        $findMock = new PHPUnit_Extensions_MockStaticMethod(
            'Twocents_Comment::find', $this->_subject
        );
        $findMock->expects($this->any())->will($this->returnValue($commentSpy));
        $this->_subject->renderComments('foo');
    }

    /**
     * Tests that a comment is not deleted from the front-end.
     *
     * @return void
     */
    public function testDoesNotDeleteCommentFromFrontEnd()
    {
        $this->_defineConstant('XH_ADM', false);
        $_POST = array(
            'twocents_action' => 'remove_comment',
            'twocents_id' => '1a2b3c'
        );
        $findMock = new PHPUnit_Extensions_MockStaticMethod(
            'Twocents_Comment::find', $this->_subject
        );
        $findMock->expects($this->never());
        $this->_subject->renderComments('foo');
    }

    /**
     * (Re)defines a constant.
     *
     * @param string $name  A name.
     * @param string $value A value.
     *
     * @return void
     */
    private function _defineConstant($name, $value)
    {
        if (!defined($name)) {
            define($name, $value);
        } else {
            runkit_constant_redefine($name, $value);
        }
    }
}

?>
