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

require_once './vendor/autoload.php';
require_once '../../cmsimple/classes/CSRFProtection.php';
require_once '../../cmsimple/functions.php';
require_once '../utf8/utf8.php';
require_once './classes/Service.php';
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
     * The comments view mock.
     *
     * @var Twocents_CommentsView
     */
    private $_viewMock;

    /**
     * The mailer mock.
     *
     * @var Twocents_Mailer
     */
    private $_mailerMock;

    /**
     * Sets up the test fixture.
     *
     * @return void
     *
     * @global array             The configuration of the plugins.
     * @global XH_CSRFProtection The CSRF protection mock.
     */
    public function setUp()
    {
        global $plugin_cf, $_XH_csrfProtection;

        $this->_defineConstant('CMSIMPLE_URL', 'http://localhost/xh/');
        $plugin_cf = array(
            'twocents' => array(
                'order' => 'ASC',
                'email_address' => 'cmbecker69@gmx.de',
                'email_linebreak' => 'CRLF'
            )
        );
        $this->_defineConstant('XH_ADM', false);
        $this->_subject = new Twocents_Controller();
        $this->_findByTopicnameMock = new PHPUnit_Extensions_MockStaticMethod(
            'Twocents_Comment::findByTopicname', $this->_subject
        );
        $this->_viewMock = $this->getMockBuilder('Twocents_CommentsView')
            ->disableOriginalConstructor()->getMock();
        $viewMakeStub = new PHPUnit_Extensions_MockStaticMethod(
            'Twocents_CommentsView::make', $this->_subject
        );
        $viewMakeStub->expects($this->any())->will(
            $this->returnValue($this->_viewMock)
        );
        $_XH_csrfProtection = $this->getMockBuilder('XH_CSRFProtection')
            ->disableOriginalConstructor()->getMock();
        $this->_mailerMock = $this->getMockBuilder('Twocents_Mailer')
            ->disableOriginalConstructor()->getMock();
        $mailerMakeMock = new PHPUnit_Extensions_MockStaticMethod(
            'Twocents_Mailer::make', $this->_subject
        );
        $mailerMakeMock->expects($this->any())->will(
            $this->returnValue($this->_mailerMock)
        );
    }

    /**
     * Tests that the comments are rendered.
     *
     * @return void
     */
    public function testRenderComments()
    {
        $this->_viewMock->expects($this->once())->method('render');
        $this->_findByTopicnameMock->expects($this->once())->with('foo');
        $this->_subject->renderComments('foo');
    }

    /**
     * Tests that an error is rendered for an invalid topicname.
     *
     * @return void
     */
    public function testRendersErrorForInvalidTopicName()
    {
        $this->assertTag(
            array(
                'tag' => 'p'
            ),
            $this->_subject->renderComments('foo bar')
        );
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
        $this->_mailerMock->expects($this->any())->method('isValidAddress')
            ->will($this->returnValue(true));
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
            'twocents_email' => 'cmb',
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
     * Tests that adding a comment sends an email notification.
     *
     * @return void
     */
    public function testAddingCommentSendsEmailNotification()
    {
        $_POST = array(
            'twocents_action' => 'add_comment',
            'twocents_user' => 'cmb',
            'twocents_email' => 'me@example.com',
            'twocents_message' => 'blah blah'
        );
        $_SERVER['QUERY_STRING'] = 'Page';
        $this->_mailerMock->expects($this->any())->method('isValidAddress')
            ->will($this->returnValue(true));
        $this->_mailerMock->expects($this->once())->method('send');
        $commentSpy = $this->getMockBuilder('Twocents_Comment')
            ->disableOriginalConstructor()
            ->setMethods(array('insert'))
            ->getMock();
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
        $this->_mailerMock->expects($this->any())->method('isValidAddress')
            ->will($this->returnValue(true));
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
