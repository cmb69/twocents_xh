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

class MailerTest extends TestCase
{
    /**
     * @var Mailer
     */
    protected $subject;

    /**
     * @var object
     */
    protected $mailMock;

    public function setUp()
    {
        $this->subject = new Mailer();
    }

    public function testValidAddress()
    {
        uopz_set_return('gethostbyname', '127.0.0.1');
        $this->assertTrue($this->subject->isValidAddress('me@example.com'));
        uopz_unset_return('gethostbyname');
    }

    public function testLocalPartWithSpaceIsInvalidAddress()
    {
        $this->assertFalse($this->subject->isValidAddress('c b@example.com'));
    }

    public function testNotExistingDomainIsInvalidAddress()
    {
        uopz_set_return('gethostbyname', 'test.invalid');
        $this->assertFalse($this->subject->isValidAddress('me@test.invalid'));
        uopz_unset_return('gethostbyname');
    }

    public function testSendAsciiSubjectCallsMailWithCorrectArguments()
    {
        uopz_set_return('mail', function() use (&$args) {
            $args = func_get_args();
        }, true);
        $this->subject->send(
            'cmbecker69@gmx',
            'A test',
            'Lorem ipsum',
            'From: cmbecker69@gmx.de'
        );
        $this->assertEquals([
            'cmbecker69@gmx',
            'A test',
            "TG9yZW0gaXBzdW0=\r\n",
            "MIME-Version: 1.0\r\n"
            . "Content-Type: text/plain; charset=UTF-8; format=flowed\r\n"
            . "Content-Transfer-Encoding: base64\r\n"
            . "From: cmbecker69@gmx.de"],
            $args
        );
        uopz_unset_return('mail');
    }

    public function testSendUtf8SubjectCallsMailWithCorrectArguments()
    {
        uopz_set_return('mail', function () use (&$args) {
            $args = func_get_args();
        }, true);
        $this->subject->send(
            'cmbecker69@gmx',
            "Driving your BMW down the road, is Fahrvergn\xC3\xBCgen",
            'Lorem ipsum',
            'From: cmbecker69@gmx.de'
        );
        $this->assertEquals([
            'cmbecker69@gmx',
            '=?UTF-8?B?RHJpdmluZyB5b3VyIEJNVyBkb3duIHRoZSByb2FkLCBpcyBGYWhydmVy'
            . "Z24=?=\r\n =?UTF-8?B?w7xnZW4=?=",
            "TG9yZW0gaXBzdW0=\r\n",
            "MIME-Version: 1.0\r\n"
            . "Content-Type: text/plain; charset=UTF-8; format=flowed\r\n"
            . "Content-Transfer-Encoding: base64\r\n"
            . "From: cmbecker69@gmx.de"],
            $args
        );
        uopz_unset_return('mail');
    }
}
