<?php

/**
 * Testing the mailer.
 *
 * PHP version 5
 *
 * @category  Testing
 * @package   Twocents
 * @author    Christoph M. Becker <cmbecker69@gmx.de>
 * @copyright 2014 Christoph M. Becker <http://3-magi.net>
 * @license   http://www.gnu.org/licenses/gpl-3.0.en.html GNU GPLv3
 * @version   SVN: $Id$
 * @link      http://3-magi.net/?CMSimple_XH/Twocents_XH
 */

require_once './vendor/autoload.php';
require_once './classes/Service.php';

/**
 * Testing the mailer.
 *
 * @category Testing
 * @package  Twocents
 * @author   Christoph M. Becker <cmbecker69@gmx.de>
 * @license  http://www.gnu.org/licenses/gpl-3.0.en.html GNU GPLv3
 * @link     http://3-magi.net/?CMSimple_XH/Twocents_XH
 */
class MailerTest extends PHPUnit_Framework_TestCase
{
    /**
     * The test subject.
     *
     * @var Twocents_Mailer
     */
    private $_subject;

    /**
     * The gethostbyname() mock.
     *
     * @var object
     */
    private $_gethostbynameMock;

    /**
     * The mail() mock.
     *
     * @var object
     */
    private $_mailMock;

    /**
     * Sets up the test fixture.
     *
     * @return void
     */
    public function setUp()
    {
        $this->_subject = Twocents_Mailer::make();
        $this->_gethostbynameMock = new PHPUnit_Extensions_MockFunction(
            'gethostbyname', $this->_subject
        );
        $this->_mailMock = new PHPUnit_Extensions_MockFunction(
            'mail', $this->_subject
        );
    }

    /**
     * Tests a valid address.
     *
     * @return void
     */
    public function testValidAddress()
    {
        $this->_gethostbynameMock->expects($this->any())->will(
            $this->returnValue('127.0.0.1')
        );
        $this->assertTrue($this->_subject->isValidAddress('me@example.com'));
    }

    /**
     * Tests the a local part with a space is an invalid address.
     *
     * @return void
     */
    public function testLocalPartWithSpaceIsInvalidAddress()
    {
        $this->assertFalse($this->_subject->isValidAddress('c b@example.com'));
    }

    /**
     * Tests that a not existing domain is an invalid address.
     *
     * @return void
     */
    public function testNotExistingDomainIsInvalidAddress()
    {
        $this->_gethostbynameMock->expects($this->any())->will(
            $this->returnValue('test.invalid')
        );
        $this->assertFalse($this->_subject->isValidAddress('me@test.invalid'));
    }

    /**
     * Tests that sending an ASCII subject calls mail with correct arguments.
     *
     * @return void
     */
    public function testSendAsciiSubjectCallsMailWithCorrectArguments()
    {
        $this->_mailMock->expects($this->once())->with(
            'cmbecker69@gmx',
            'A test',
            "TG9yZW0gaXBzdW0=\r\n",
            "MIME-Version: 1.0\r\n"
            . "Content-Type: text/plain; charset=UTF-8; format=flowed\r\n"
            . "Content-Transfer-Encoding: base64\r\n"
            . "From: cmbecker69@gmx.de"
        );
        $this->_subject->send(
            'cmbecker69@gmx',
            'A test',
            'Lorem ipsum',
            'From: cmbecker69@gmx.de'
        );
    }

    /**
     * Tests that sending an UTF-8 subject calls mail with correct arguments.
     *
     * @return void
     */
    public function testSendUtf8SubjectCallsMailWithCorrectArguments()
    {
        $this->_mailMock->expects($this->once())->with(
            'cmbecker69@gmx',
            '=?UTF-8?B?RHJpdmluZyB5b3VyIEJNVyBkb3duIHRoZSByb2FkLCBpcyBGYWhydmVy'
            . "Z24=?=\r\n =?UTF-8?B?w7xnZW4=?=",
            "TG9yZW0gaXBzdW0=\r\n",
            "MIME-Version: 1.0\r\n"
            . "Content-Type: text/plain; charset=UTF-8; format=flowed\r\n"
            . "Content-Transfer-Encoding: base64\r\n"
            . "From: cmbecker69@gmx.de"
        );
        $this->_subject->send(
            'cmbecker69@gmx',
            "Driving your BMW down the road, is Fahrvergn\xC3\xBCgen",
            'Lorem ipsum',
            'From: cmbecker69@gmx.de'
        );
    }
}

?>
