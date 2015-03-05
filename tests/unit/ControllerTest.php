<?php

/**
 * Testing the controllers.
 *
 * PHP version 5
 *
 * @category  Testing
 * @package   Twocents
 * @author    Christoph M. Becker <cmbecker69@gmx.de>
 * @copyright 2014-2015 Christoph M. Becker <http://3-magi.net/>
 * @license   http://www.gnu.org/licenses/gpl-3.0.en.html GNU GPLv3
 * @link      http://3-magi.net/?CMSimple_XH/Twocents_XH
 */

/**
 * Testing the controllers.
 *
 * @category CMSimple_XH
 * @package  Twocents
 * @author   Christoph M. Becker <cmbecker69@gmx.de>
 * @license  http://www.gnu.org/licenses/gpl-3.0.en.html GNU GPLv3
 * @link     http://3-magi.net/?CMSimple_XH/Twocents_XH
 */
class ControllerTest extends TestCase
{
    /**
     * The test subject.
     *
     * @var Twocents_Controller
     */
    protected $subject;

    /**
     * The findByTopicname mock.
     *
     * @var object
     */
    protected $findByTopicnameMock;

    /**
     * The comments view mock.
     *
     * @var Twocents_CommentsView
     */
    protected $viewMock;

    /**
     * The mailer mock.
     *
     * @var Twocents_Mailer
     */
    protected $mailerMock;

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

        $this->defineConstant('CMSIMPLE_URL', 'http://localhost/xh/');
        $plugin_cf = array(
            'twocents' => array(
                'comments_moderated' => '',
                'comments_order' => 'ASC',
                'comments_markup' => '',
                'email_address' => 'cmbecker69@gmx.de',
                'email_linebreak' => 'CRLF',
                'captcha_plugin' => ''
            )
        );
        $this->defineConstant('XH_ADM', false);
        $this->subject = new Twocents_Controller();
        $this->findByTopicnameMock = new PHPUnit_Extensions_MockStaticMethod(
            'Twocents_Comment::findByTopicname', $this->subject
        );
        $this->viewMock = $this->getMockBuilder('Twocents_CommentsView')
            ->disableOriginalConstructor()->getMock();
        $viewMakeStub = new PHPUnit_Extensions_MockStaticMethod(
            'Twocents_CommentsView::make', $this->subject
        );
        $viewMakeStub->expects($this->any())->will(
            $this->returnValue($this->viewMock)
        );
        $_XH_csrfProtection = $this->getMockBuilder('XH_CSRFProtection')
            ->disableOriginalConstructor()->getMock();
        $this->mailerMock = $this->getMockBuilder('Twocents_Mailer')
            ->disableOriginalConstructor()->getMock();
        $mailerMakeMock = new PHPUnit_Extensions_MockStaticMethod(
            'Twocents_Mailer::make', $this->subject
        );
        $mailerMakeMock->expects($this->any())->will(
            $this->returnValue($this->mailerMock)
        );
    }

    /**
     * Tests that the comments are rendered.
     *
     * @return void
     */
    public function testRenderComments()
    {
        $this->viewMock->expects($this->once())->method('render');
        $this->findByTopicnameMock->expects($this->once())->with('foo');
        $this->subject->renderComments('foo');
    }

    /**
     * Tests that an error is rendered for an invalid topicname.
     *
     * @return void
     */
    public function testRendersErrorForInvalidTopicName()
    {
        @$this->assertTag(
            array(
                'tag' => 'p'
            ),
            $this->subject->renderComments('foo bar')
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
        $this->mailerMock->expects($this->any())->method('isValidAddress')
            ->will($this->returnValue(true));
        $commentSpy = $this->getMockBuilder('Twocents_Comment')
            ->disableOriginalConstructor()
            ->setMethods(array('insert'))
            ->getMock();
        $commentSpy->expects($this->once())->method('insert');
        $makeMock = new PHPUnit_Extensions_MockStaticMethod(
            'Twocents_Comment::make', $this->subject
        );
        $makeMock->expects($this->once())->will($this->returnValue($commentSpy));
        $this->subject->renderComments('foo');
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
        $this->assertDoesNotAddInvalidComment();
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
        $this->assertDoesNotAddInvalidComment();
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
        $this->assertDoesNotAddInvalidComment();
    }

    /**
     * Asserts that an invalid comment is not added.
     *
     * @return void
     */
    protected function assertDoesNotAddInvalidComment()
    {
        $_SERVER['QUERY_STRING'] = 'Page';
        $commentSpy = $this->getMockBuilder('Twocents_Comment')
            ->disableOriginalConstructor()
            ->setMethods(array('insert'))
            ->getMock();
        $commentSpy->expects($this->never())->method('insert');
        $makeMock = new PHPUnit_Extensions_MockStaticMethod(
            'Twocents_Comment::make', $this->subject
        );
        $makeMock->expects($this->once())->will($this->returnValue($commentSpy));
        $this->subject->renderComments('foo');
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
        $this->mailerMock->expects($this->any())->method('isValidAddress')
            ->will($this->returnValue(true));
        $this->mailerMock->expects($this->once())->method('send');
        $commentSpy = $this->getMockBuilder('Twocents_Comment')
            ->disableOriginalConstructor()
            ->setMethods(array('insert'))
            ->getMock();
        $makeMock = new PHPUnit_Extensions_MockStaticMethod(
            'Twocents_Comment::make', $this->subject
        );
        $makeMock->expects($this->once())->will($this->returnValue($commentSpy));
        $this->subject->renderComments('foo');
    }

    /**
     * Tests that a comment is updated.
     *
     * @return void
     */
    public function testUpdateComment()
    {
        $this->defineConstant('XH_ADM', true);
        $_POST = array(
            'twocents_action' => 'update_comment',
            'twocents_id' => '1a2b3c',
            'twocents_user' => 'cmb',
            'twocents_email' => 'me@example.com',
            'twocents_message' => 'blah blah'
        );
        $_SERVER['QUERY_STRING'] = 'Page';
        $this->mailerMock->expects($this->any())->method('isValidAddress')
            ->will($this->returnValue(true));
        $commentSpy = $this->getMockBuilder('Twocents_Comment')
            ->disableOriginalConstructor()
            ->setMethods(array('update'))
            ->getMock();
        $commentSpy->expects($this->once())->method('update');
        $makeMock = new PHPUnit_Extensions_MockStaticMethod(
            'Twocents_Comment::find', $this->subject
        );
        $makeMock->expects($this->once())->will($this->returnValue($commentSpy));
        $this->subject->renderComments('foo');
    }

    /**
     * Tests that a comment with an invalid username is not updated.
     *
     * @return void
     */
    public function testDoesNotUpdateInvalidUserComment()
    {
        $this->defineConstant('XH_ADM', true);
        $_POST = array(
            'twocents_action' => 'update_comment',
            'twocents_id' => '1a2b3c',
            'twocents_user' => '',
            'twocents_email' => 'me@example.com',
            'twocents_message' => 'blah blah'
        );
        $this->assertDoesNotUpdateInvalidComment();
    }

    /**
     * Tests that a comment with an invalid email address is not updated.
     *
     * @return void
     */
    public function testDoesNotUpdateInvalidEmailComment()
    {
        $this->defineConstant('XH_ADM', true);
        $_POST = array(
            'twocents_action' => 'update_comment',
            'twocents_id' => '1a2b3c',
            'twocents_user' => 'cmb',
            'twocents_email' => '',
            'twocents_message' => 'blah blah'
        );
        $this->assertDoesNotUpdateInvalidComment();
    }

    /**
     * Tests that a comment with an invalid comment message is not updated.
     *
     * @return void
     */
    public function testDoesNotUpdateInvalidMessageComment()
    {
        $this->defineConstant('XH_ADM', true);
        $_POST = array(
            'twocents_action' => 'update_comment',
            'twocents_id' => '1a2b3c',
            'twocents_user' => 'cmb',
            'twocents_email' => 'me@example.com',
            'twocents_message' => ''
        );
        $this->assertDoesNotUpdateInvalidComment();
    }

    /**
     * Asserts that an invalid comment is not updated.
     *
     * @return void
     */
    protected function assertDoesNotUpdateInvalidComment()
    {
        $_SERVER['QUERY_STRING'] = 'Page';
        $commentSpy = $this->getMockBuilder('Twocents_Comment')
            ->disableOriginalConstructor()
            ->setMethods(array('update'))
            ->getMock();
        $commentSpy->expects($this->never())->method('update');
        $makeMock = new PHPUnit_Extensions_MockStaticMethod(
            'Twocents_Comment::find', $this->subject
        );
        $makeMock->expects($this->once())->will($this->returnValue($commentSpy));
        $this->subject->renderComments('foo');
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
            'Twocents_Comment::find', $this->subject
        );
        $makeMock->expects($this->never());
        $this->subject->renderComments('foo');
    }

    /**
     * Tests that a comment is deleted.
     *
     * @return void
     */
    public function testDeleteComment()
    {
        $this->defineConstant('XH_ADM', true);
        $_POST = array(
            'twocents_action' => 'remove_comment',
            'twocents_id' => '1a2b3c'
        );
        $commentSpy = $this->getMockBuilder('Twocents_Comment')
            ->disableOriginalConstructor()->getMock();
        $commentSpy->expects($this->once())->method('delete');
        $findMock = new PHPUnit_Extensions_MockStaticMethod(
            'Twocents_Comment::find', $this->subject
        );
        $findMock->expects($this->any())->will($this->returnValue($commentSpy));
        $this->subject->renderComments('foo');
    }

    /**
     * Tests that a comment is not deleted from the front-end.
     *
     * @return void
     */
    public function testDoesNotDeleteCommentFromFrontEnd()
    {
        $this->defineConstant('XH_ADM', false);
        $_POST = array(
            'twocents_action' => 'remove_comment',
            'twocents_id' => '1a2b3c'
        );
        $findMock = new PHPUnit_Extensions_MockStaticMethod(
            'Twocents_Comment::find', $this->subject
        );
        $findMock->expects($this->never());
        $this->subject->renderComments('foo');
    }
}

?>
