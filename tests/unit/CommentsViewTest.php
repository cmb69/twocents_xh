<?php

/**
 * Testing the comments views.
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
require_once './classes/DataSource.php';
require_once './classes/Presentation.php';

/**
 * Testing the comments views.
 *
 * @category CMSimple_XH
 * @package  Twocents
 * @author   Christoph M. Becker <cmbecker69@gmx.de>
 * @license  http://www.gnu.org/licenses/gpl-3.0.en.html GNU GPLv3
 * @link     http://3-magi.net/?CMSimple_XH/Twocents_XH
 *
 * @todo Test via controller.
 */
class CommentsViewTest extends PHPUnit_Framework_TestCase
{
    /**
     * The comment ID.
     */
    const ID = '1a2b3c';

    /**
     * The comment timestamp.
     */
    const TIME = 1406638537;

    /**
     * The username of the poster.
     */
    const USER = '>cmb<';

    /**
     * The email address of the poster.
     */
    const EMAIL = 'me@example.com';

    /**
     * The comment message.
     */
    const MESSAGE = 'blah < blah';

    /**
     * The test subject.
     *
     * @var Twocents_CommentsView
     */
    private $_subject;

    /**
     * Sets up the test fixture.
     *
     * @return void
     *
     * @global array             The localization of the plugins.
     * @global XH_CSRFProtection The CSRF protection mock.
     */
    public function setUp()
    {
        global $plugin_tx, $_XH_csrfProtection;

        $this->_defineConstant('XH_ADM', false);
        $_SERVER['QUERY_STRING'] = 'Page';
        $plugin_tx = array(
            'twocents' => array(
                'format_date' => 'n/j/Y',
                'format_heading' => 'On {DATE} at {TIME} {USER} wrote:',
                'format_time' => 'g:ia',
                'label_add' => 'Add Comment',
                'label_delete' => 'Delete',
                'label_edit' => 'Edit',
                'label_email' => 'Email',
                'label_message' => 'Message',
                'label_reset' => 'Reset',
                'label_update' => 'Update Comment',
                'label_user' => 'Username'
            )
        );
        $commentStub = $this->getMockBuilder('Twocents_Comment')
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
        $this->_subject = Twocents_CommentsView::make(
            array($commentStub, $commentStub, $commentStub), null
        );
        $_XH_csrfProtection = $this->getMockBuilder('XH_CSRFProtection')
            ->disableOriginalConstructor()->getMock();
    }

    /**
     * Tests that an ul element with 3 children is rendered.
     *
     * @return void
     */
    public function testRendersUlWith3Children()
    {
        $this->assertTag(
            array(
                'tag' => 'ul',
                'attributes' => array('class' => 'twocents_comments'),
                'children' => array(
                    'only' => array(
                        'tag' => 'li'
                    ),
                    'count' => 3
                )
            ),
            $this->_subject->render()
        );
    }

    /**
     * Tests that a li element with the comment heading is rendered.
     *
     * @return void
     */
    public function testRendersLiWithHeading()
    {
        $this->assertTag(
            array(
                'tag' => 'li',
                'child' => array(
                    'tag' => 'p',
                    'content' => 'On 7/29/2014 at 2:55pm >cmb< wrote'
                )
            ),
            $this->_subject->render()
        );
    }

    /**
     * Tests that a li element with the comment message is rendered.
     *
     * @return void
     */
    public function testRendersLiWithMessage()
    {
        $this->assertTag(
            array(
                'tag' => 'li',
                'child' => array(
                    'tag' => 'blockquote',
                    'content' => self::MESSAGE
                )
            ),
            $this->_subject->render()
        );
    }

    /**
     * Tests that the admin tools are rendered.
     *
     * @return void
     */
    public function testRendersAdminTools()
    {
        $this->_defineConstant('XH_ADM', true);
        $this->assertTag(
            array(
                'tag' => 'div',
                'attributes' => array('class' => 'twocents_admin_tools')
            ),
            $this->_subject->render()
        );
    }

    /**
     * Tests that an edit link is rendered.
     *
     * @return void
     */
    public function testRendersEditLink()
    {
        $this->_defineConstant('XH_ADM', true);
        $this->assertTag(
            array(
                'tag' => 'a',
                'content' => 'Edit'
            ),
            $this->_subject->render()
        );
    }

    /**
     * Tests that a delete form is rendered.
     *
     * @return void
     */
    public function testRendersDeleteForm()
    {
        $this->_defineConstant('XH_ADM', true);
        $this->assertTag(
            array(
                'tag' => 'form',
                'attributes' => array(
                    'method' => 'post'
                )
            ),
            $this->_subject->render()
        );
    }

    /**
     * Tests that a delete button is rendered.
     *
     * @return void
     */
    public function testRendersDeleteButton()
    {
        $this->_defineConstant('XH_ADM', true);
        $this->assertTag(
            array(
                'tag' => 'button',
                'content' => 'Delete',
                'attributes' => array(
                    'name' => 'twocents_action',
                    'value' => 'remove_comment'
                )
            ),
            $this->_subject->render()
        );
    }

    /**
     * Tests that a delete id input is rendered.
     *
     * @return void
     */
    public function testRendersDeleteId()
    {
        $this->_defineConstant('XH_ADM', true);
        $this->assertTag(
            array(
                'tag' => 'input',
                'attributes' => array(
                    'type' => 'hidden',
                    'name' => 'twocents_id',
                    'value' => '1a2b3c'
                )
            ),
            $this->_subject->render()
        );
    }

    /**
     * Tests that the comment form is rendered.
     *
     * @return void
     */
    public function testRendersCommentForm()
    {
        $this->assertTag(
            array(
                'tag' => 'form',
                'attributes' => array(
                    'method' => 'post',
                    'class' => 'twocents_form'
                )
            ),
            $this->_subject->render()
        );
    }

    /**
     * Tests that a id input is rendered.
     *
     * @return void
     */
    public function testRendersIdInput()
    {
        $this->assertTag(
            array(
                'tag' => 'input',
                'attributes' => array(
                    'type' => 'hidden',
                    'name' => 'twocents_id',
                    //'value' => ''
                )
            ),
            $this->_subject->render()
        );
    }

    //public function testRendersIdInputWhenEditing()
    //{
    //    var_dump($this->_subject->render());
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
    //        $this->_subject->render()
    //    );
    //}

    /**
     * Tests that a user input is rendered.
     *
     * @return void
     */
    public function testRendersUserInput()
    {
        $this->assertTag(
            array(
                'tag' => 'label',
                'content' => 'Username',
                'child' => array(
                    'tag' => 'input',
                    'attributes' => array(
                        'type' => 'text',
                        'name' => 'twocents_user',
                        //'value' => ''
                    ),
                )
            ),
            $this->_subject->render()
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
    //        $this->_subject->render()
    //    );
    //}

    /**
     * Tests that an email input is rendered.
     *
     * @return void
     */
    public function testRendersEmailInput()
    {
        $this->assertTag(
            array(
                'tag' => 'label',
                'content' => 'Email',
                'child' => array(
                    'tag' => 'input',
                    'attributes' => array(
                        'type' => 'text',
                        'name' => 'twocents_email',
                        //'value' => ''
                    ),
                )
            ),
            $this->_subject->render()
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
    //        $this->_subject->render()
    //    );
    //}

    /**
     * Tests that a message textarea is rendered.
     *
     * @return void
     */
    public function testRendersMessageTextarea()
    {
        $this->assertTag(
            array(
                'tag' => 'label',
                'content' => 'Message',
                'child' => array(
                    'tag' => 'textarea',
                    'attributes' => array('name' => 'twocents_message'),
                    //'content' => ''
                )
            ),
            $this->_subject->render()
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
    //        $this->_subject->render()
    //    );
    //}

    /**
     * Tests that a submit button is rendered.
     *
     * @return void
     */
    public function testRendersSubmitButton()
    {
        $this->assertTag(
            array(
                'tag' => 'button',
                'attributes' => array(
                    'name' => 'twocents_action',
                    'value' => 'add_comment'
                ),
                'content' => 'Add Comment'
            ),
            $this->_subject->render()
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
    //        $this->_subject->render()
    //    );
    //}

    /**
     * Tests that a reset button is rendered.
     *
     * @return void
     */
    public function testRendersResetButton()
    {
        $this->assertTag(
            array(
                'tag' => 'button',
                'attributes' => array('type' => 'reset'),
                'content' => 'Reset'
            ),
            $this->_subject->render()
        );
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
