<?php

/**
 * Copyright 2014-2017 Christoph M. Becker
 *
 * This file is part of Twocents_XH.
 *
 * Twocents_XH is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Twocents_XH is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Twocents_XH.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace Twocents;

use PHPUnit_Extensions_MockStaticMethod;

class ControllerTest extends TestCase
{
    /**
     * @var Controller
     */
    protected $subject;

    /**
     * @var object
     */
    protected $findByTopicnameMock;

    /**
     * @var CommentsView
     */
    protected $viewMock;

    /**
     * @var Mailer
     */
    protected $mailerMock;

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
        $this->subject = new Controller();
        $this->findByTopicnameMock = new PHPUnit_Extensions_MockStaticMethod(
            'Twocents\\Comment::findByTopicname',
            $this->subject
        );
        $this->viewMock = $this->getMockBuilder('Twocents\\CommentsView')
            ->disableOriginalConstructor()->getMock();
        $viewMakeStub = new PHPUnit_Extensions_MockStaticMethod('Twocents\\CommentsView::make', $this->subject);
        $viewMakeStub->expects($this->any())->will(
            $this->returnValue($this->viewMock)
        );
        $_XH_csrfProtection = $this->getMockBuilder('XH_CSRFProtection')
            ->disableOriginalConstructor()->getMock();
        $this->mailerMock = $this->getMockBuilder('Twocents\\Mailer')
            ->disableOriginalConstructor()->getMock();
        $mailerMakeMock = new PHPUnit_Extensions_MockStaticMethod('Twocents\\Mailer::make', $this->subject);
        $mailerMakeMock->expects($this->any())->will(
            $this->returnValue($this->mailerMock)
        );
    }

    public function testRenderComments()
    {
        $this->viewMock->expects($this->once())->method('render');
        $this->findByTopicnameMock->expects($this->once())->with('foo');
        $this->subject->renderComments('foo');
    }

    public function testRendersErrorForInvalidTopicName()
    {
        @$this->assertTag(
            array(
                'tag' => 'p'
            ),
            $this->subject->renderComments('foo bar')
        );
    }

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
        $commentSpy = $this->getMockBuilder('Twocents\\Comment')
            ->disableOriginalConstructor()
            ->setMethods(array('insert'))
            ->getMock();
        $commentSpy->expects($this->once())->method('insert');
        $makeMock = new PHPUnit_Extensions_MockStaticMethod('Twocents\\Comment::make', $this->subject);
        $makeMock->expects($this->once())->will($this->returnValue($commentSpy));
        $this->subject->renderComments('foo');
    }

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

    protected function assertDoesNotAddInvalidComment()
    {
        $_SERVER['QUERY_STRING'] = 'Page';
        $commentSpy = $this->getMockBuilder('Twocents\\Comment')
            ->disableOriginalConstructor()
            ->setMethods(array('insert'))
            ->getMock();
        $commentSpy->expects($this->never())->method('insert');
        $makeMock = new PHPUnit_Extensions_MockStaticMethod('Twocents\\Comment::make', $this->subject);
        $makeMock->expects($this->once())->will($this->returnValue($commentSpy));
        $this->subject->renderComments('foo');
    }

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
        $commentSpy = $this->getMockBuilder('Twocents\\Comment')
            ->disableOriginalConstructor()
            ->setMethods(array('insert'))
            ->getMock();
        $makeMock = new PHPUnit_Extensions_MockStaticMethod('Twocents\\Comment::make', $this->subject);
        $makeMock->expects($this->once())->will($this->returnValue($commentSpy));
        $this->subject->renderComments('foo');
    }

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
        $commentSpy = $this->getMockBuilder('Twocents\\Comment')
            ->disableOriginalConstructor()
            ->setMethods(array('update'))
            ->getMock();
        $commentSpy->expects($this->once())->method('update');
        $makeMock = new PHPUnit_Extensions_MockStaticMethod('Twocents\\Comment::find', $this->subject);
        $makeMock->expects($this->once())->will($this->returnValue($commentSpy));
        $this->subject->renderComments('foo');
    }

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

    protected function assertDoesNotUpdateInvalidComment()
    {
        $_SERVER['QUERY_STRING'] = 'Page';
        $commentSpy = $this->getMockBuilder('Twocents\\Comment')
            ->disableOriginalConstructor()
            ->setMethods(array('update'))
            ->getMock();
        $commentSpy->expects($this->never())->method('update');
        $makeMock = new PHPUnit_Extensions_MockStaticMethod('Twocents\\Comment::find', $this->subject);
        $makeMock->expects($this->once())->will($this->returnValue($commentSpy));
        $this->subject->renderComments('foo');
    }

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
        $makeMock = new PHPUnit_Extensions_MockStaticMethod('Twocents\\Comment::find', $this->subject);
        $makeMock->expects($this->never());
        $this->subject->renderComments('foo');
    }

    public function testDeleteComment()
    {
        $this->defineConstant('XH_ADM', true);
        $_POST = array(
            'twocents_action' => 'remove_comment',
            'twocents_id' => '1a2b3c'
        );
        $commentSpy = $this->getMockBuilder('Twocents\\Comment')
            ->disableOriginalConstructor()->getMock();
        $commentSpy->expects($this->once())->method('delete');
        $findMock = new PHPUnit_Extensions_MockStaticMethod('Twocents\\Comment::find', $this->subject);
        $findMock->expects($this->any())->will($this->returnValue($commentSpy));
        $this->subject->renderComments('foo');
    }

    public function testDoesNotDeleteCommentFromFrontEnd()
    {
        $this->defineConstant('XH_ADM', false);
        $_POST = array(
            'twocents_action' => 'remove_comment',
            'twocents_id' => '1a2b3c'
        );
        $findMock = new PHPUnit_Extensions_MockStaticMethod('Twocents\\Comment::find', $this->subject);
        $findMock->expects($this->never());
        $this->subject->renderComments('foo');
    }
}
