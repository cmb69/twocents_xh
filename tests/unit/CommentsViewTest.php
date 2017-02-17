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

class CommentsViewTest extends TestCase
{
    const ID = '1a2b3c';

    const TIME = 1406638537;

    const USER = '>cmb<';

    const EMAIL = 'me@example.com';

    const MESSAGE = "blah < blah\nblah";

    /**
     * @var CommentsView
     */
    protected $subject;

    public function setUp()
    {
        global $plugin_tx, $_XH_csrfProtection;

        $this->defineConstant('XH_ADM', false);
        $_SERVER['QUERY_STRING'] = 'Page';
        $plugin_tx = array(
            'twocents' => array(
                'format_date' => 'n/j/Y',
                'format_heading' => 'On {DATE} at {TIME} {USER} wrote:',
                'format_time' => 'g:ia',
                'label_new' => 'Write new comment',
                'label_add' => 'Add Comment',
                'label_edit' => 'Edit',
                'label_hide' => 'Hide',
                'label_show' => 'Show',
                'label_delete' => 'Delete',
                'label_email' => 'Email',
                'label_message' => 'Message',
                'label_reset' => 'Reset',
                'label_update' => 'Update Comment',
                'label_user' => 'Username',
                'label_bold' => 'Bold',
                'label_italic' => 'Italic',
                'label_link' => 'Link',
                'label_unlink' => 'Unlink',
                'message_delete' => 'Delete?',
                'message_link' => 'URL to link'
            )
        );
        $commentStub = $this->getMockBuilder('Twocents\\Comment')
            ->disableOriginalConstructor()->getMock();
        $commentStub->expects($this->any())->method('getId')->will(
            $this->returnValue(self::ID)
        );
        $commentStub->expects($this->any())->method('getTime')->will(
            $this->returnValue(self::TIME)
        );
        $commentStub->expects($this->any())->method('getUser')->will(
            $this->returnValue(self::USER)
        );
        $commentStub->expects($this->any())->method('getEmail')->will(
            $this->returnValue(self::EMAIL)
        );
        $commentStub->expects($this->any())->method('getMessage')->will(
            $this->returnValue(self::MESSAGE)
        );
        $commentStub->expects($this->any())->method('isVisible')->will(
            $this->returnValue(true)
        );
        $this->subject = CommentsView::make(array($commentStub, $commentStub, $commentStub), null);
        $_XH_csrfProtection = $this->getMockBuilder('XH_CSRFProtection')
            ->disableOriginalConstructor()->getMock();
    }

    public function testRendersMainScriptToBjs()
    {
        global $bjs;

        $this->subject->render();
        @$this->assertTag(
            array(
                'tag' => 'script',
                'attributes' => array(
                    'type' => 'text/javascript',
                    'src' => 'twocents/twocents.js'
                )
            ),
            $bjs
        );
    }

    public function testRendersConfigScriptToBjs()
    {
        global $bjs;

        $this->subject->render();
        @$this->assertTag(
            array(
                'tag' => 'script',
                'attributes' => array('type' => 'text/javascript'),
                'content' => 'var TWOCENTS = {'
            ),
            $bjs
        );
    }

    public function testRendersDivWith3Children()
    {
        @$this->assertTag(
            array(
                'tag' => 'div',
                'attributes' => array('class' => 'twocents_comments'),
                'children' => array(
                    'only' => array(
                        'tag' => 'div',
                        'attributes' => (array('class' => 'twocents_comment'))
                    ),
                    'count' => 3
                )
            ),
            $this->subject->render()
        );
    }

    public function testRendersCommentAttribution()
    {
        @$this->assertTag(
            array(
                'tag' => 'div',
                'id' => 'twocents_comment_' . self::ID,
                'attributes' => array('class' => 'twocents_comment'),
                'child' => array(
                    'tag' => 'div',
                    'attributes' => array('class' => 'twocents_attribution'),
                    'content' => 'On 7/29/2014 at 2:55pm >cmb< wrote'
                )
            ),
            $this->subject->render()
        );
    }

    public function testRendersCommentMessage()
    {
        @$this->assertTag(
            array(
                'tag' => 'div',
                'attributes' => array('class' => 'twocents_comment'),
                'child' => array(
                    'tag' => 'div',
                    'attributes' => array('class' => 'twocents_message'),
                    'content' => 'blah < blah',
                    'child' => array('tag' => 'br')
                )
            ),
            $this->subject->render()
        );
    }

    public function testRendersAdminTools()
    {
        $this->defineConstant('XH_ADM', true);
        @$this->assertTag(
            array(
                'tag' => 'div',
                'attributes' => array('class' => 'twocents_admin_tools')
            ),
            $this->subject->render()
        );
    }

    public function testRendersEditLink()
    {
        $this->defineConstant('XH_ADM', true);
        @$this->assertTag(
            array(
                'tag' => 'a',
                'content' => 'Edit'
            ),
            $this->subject->render()
        );
    }

    public function testRendersDeleteForm()
    {
        $this->defineConstant('XH_ADM', true);
        @$this->assertTag(
            array(
                'tag' => 'form',
                'attributes' => array(
                    'method' => 'post'
                )
            ),
            $this->subject->render()
        );
    }

    public function testRendersDeleteButton()
    {
        $this->defineConstant('XH_ADM', true);
        @$this->assertTag(
            array(
                'tag' => 'button',
                'content' => 'Delete',
                'attributes' => array(
                    'name' => 'twocents_action',
                    'value' => 'remove_comment'
                )
            ),
            $this->subject->render()
        );
    }

    public function testRendersDeleteId()
    {
        $this->defineConstant('XH_ADM', true);
        @$this->assertTag(
            array(
                'tag' => 'input',
                'attributes' => array(
                    'type' => 'hidden',
                    'name' => 'twocents_id',
                    'value' => '1a2b3c'
                )
            ),
            $this->subject->render()
        );
    }

    public function testRendersCommentForm()
    {
        @$this->assertTag(
            array(
                'tag' => 'form',
                'attributes' => array(
                    'method' => 'post',
                    'class' => 'twocents_form'
                )
            ),
            $this->subject->render()
        );
    }

    public function testRendersIdInput()
    {
        @$this->assertTag(
            array(
                'tag' => 'input',
                'attributes' => array(
                    'type' => 'hidden',
                    'name' => 'twocents_id',
                    //'value' => ''
                )
            ),
            $this->subject->render()
        );
    }

    //public function testRendersIdInputWhenEditing()
    //{
    //    var_dump($this->subject->render());
    //    $_GET['twocents_id'] = self::ID;
    //    $this->assertTag(
    //        array(
    //            'tag' => 'input',
    //            'attributes' => array(
    //                'type' => 'hidden',
    //                'name' => 'twocents_id',
    //                'value' => self::ID
    //            )
    //        ),
    //        $this->subject->render()
    //    );
    //}

    public function testRendersUserInput()
    {
        @$this->assertTag(
            array(
                'tag' => 'label',
                'content' => 'Username',
                'child' => array(
                    'tag' => 'input',
                    'attributes' => array(
                        'type' => 'text',
                        'name' => 'twocents_user',
                        'size' => '20',
                        'required' => 'required'
                        //'value' => ''
                    ),
                )
            ),
            $this->subject->render()
        );
    }

    //public function testRendersUserInputWhenEditing()
    //{
    //    $_GET['twocents_id'] = self::ID;
    //    $this->assertTag(
    //        array(
    //            'tag' => 'label',
    //            'content' => 'Username',
    //            'child' => array(
    //                'tag' => 'input',
    //                'attributes' => array(
    //                    'type' => 'text',
    //                    'name' => 'twocents_user',
    //                    'value' => self::USER
    //                ),
    //            )
    //        ),
    //        $this->subject->render()
    //    );
    //}

    public function testRendersEmailInput()
    {
        @$this->assertTag(
            array(
                'tag' => 'label',
                'content' => 'Email',
                'child' => array(
                    'tag' => 'input',
                    'attributes' => array(
                        'type' => 'email',
                        'name' => 'twocents_email',
                        'size' => '20',
                        'required' => 'required'
                        //'value' => ''
                    ),
                )
            ),
            $this->subject->render()
        );
    }

    //public function testRendersEmailInputWhenEditing()
    //{
    //    $_GET['twocents_id'] = self::ID;
    //    $this->assertTag(
    //        array(
    //            'tag' => 'label',
    //            'content' => 'Email',
    //            'child' => array(
    //                'tag' => 'input',
    //                'attributes' => array(
    //                    'type' => 'text',
    //                    'name' => 'twocents_email',
    //                    'value' => self::EMAIL
    //                ),
    //            )
    //        ),
    //        $this->subject->render()
    //    );
    //}

    public function testRendersMessageTextarea()
    {
        @$this->assertTag(
            array(
                'tag' => 'label',
                'content' => 'Message',
                'child' => array(
                    'tag' => 'textarea',
                    'attributes' => array(
                        'name' => 'twocents_message',
                        'cols' => '50',
                        'rows' => '8',
                        'required' => 'required'
                    )
                    //'content' => ''
                )
            ),
            $this->subject->render()
        );
    }

    //public function testRendersMessageTextareaWhenEditing()
    //{
    //    $_GET['twocents_id'] = self::ID;
    //    $this->assertTag(
    //        array(
    //            'tag' => 'label',
    //            'content' => 'Message',
    //            'child' => array(
    //                'tag' => 'textarea',
    //                'attributes' => array('name' => 'twocents_message'),
    //                'content' => self::MESSAGE
    //            )
    //        ),
    //        $this->subject->render()
    //    );
    //}

    public function testRendersSubmitButton()
    {
        @$this->assertTag(
            array(
                'tag' => 'button',
                'attributes' => array(
                    'name' => 'twocents_action',
                    'value' => 'add_comment'
                ),
                'content' => 'Add Comment'
            ),
            $this->subject->render()
        );
    }

    //public function testRendersSubmitButtonWhenEditing()
    //{
    //    $_GET['twocents_id'] = self::ID;
    //    $this->assertTag(
    //        array(
    //            'tag' => 'button',
    //            'attributes' => array(
    //                'name' => 'twocents_action',
    //                'value' => 'update_comment'
    //            ),
    //            'content' => 'Update Comment'
    //        ),
    //        $this->subject->render()
    //    );
    //}

    public function testRendersResetButton()
    {
        @$this->assertTag(
            array(
                'tag' => 'button',
                'attributes' => array('type' => 'reset'),
                'content' => 'Reset'
            ),
            $this->subject->render()
        );
    }
}
