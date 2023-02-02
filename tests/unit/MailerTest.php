<?php

/**
 * Copyright 2014-2023 Christoph M. Becker
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

use PHPUnit\Framework\TestCase;

class MailerTest extends TestCase
{
    /**
     * @var Mailer
     */
    protected $subject;

    /**
     * @var MailHelper
     */
    protected $mailHelper;

    public function setUp(): void
    {
        $this->mailHelper = $this->createMock(MailHelper::class);
        $this->subject = new Mailer($this->mailHelper);
    }

    public function testValidAddress()
    {
        $this->mailHelper->method('gethostbyname')->willReturn('127.0.0.1');
        $this->assertTrue($this->subject->isValidAddress('me@example.com'));
    }

    public function testLocalPartWithSpaceIsInvalidAddress()
    {
        $this->assertFalse($this->subject->isValidAddress('c b@example.com'));
    }

    public function testNotExistingDomainIsInvalidAddress()
    {
        $this->mailHelper->method('gethostbyname')->willReturn('test.invalid');
        $this->assertFalse($this->subject->isValidAddress('me@test.invalid'));
    }

    public function testSendAsciiSubjectCallsMailWithCorrectArguments()
    {
        $this->mailHelper->expects($this->once())->method('mail')->with(
            $this->equalTo('cmbecker69@gmx'),
            $this->equalTo('A test'),
            $this->equalTo("TG9yZW0gaXBzdW0=\r\n"),
            $this->equalTo(
                "MIME-Version: 1.0\r\n"
                . "Content-Type: text/plain; charset=UTF-8; format=flowed\r\n"
                . "Content-Transfer-Encoding: base64\r\n"
                . "From: cmbecker69@gmx.de"
            )
        );
        $this->subject->send(
            'cmbecker69@gmx',
            'A test',
            'Lorem ipsum',
            'From: cmbecker69@gmx.de'
        );
    }

    public function testSendUtf8SubjectCallsMailWithCorrectArguments()
    {
        $this->mailHelper->expects($this->once())->method('mail')->with(
            $this->equalTo('cmbecker69@gmx'),
            $this->equalTo(
                '=?UTF-8?B?RHJpdmluZyB5b3VyIEJNVyBkb3duIHRoZSByb2FkLCBpcyBGYWhydmVy'
                . "Z24=?=\r\n =?UTF-8?B?w7xnZW4=?="
            ),
            $this->equalTo("TG9yZW0gaXBzdW0=\r\n"),
            $this->equalTo(
                "MIME-Version: 1.0\r\n"
                . "Content-Type: text/plain; charset=UTF-8; format=flowed\r\n"
                . "Content-Transfer-Encoding: base64\r\n"
                . "From: cmbecker69@gmx.de"
            )
        );
        $this->subject->send(
            'cmbecker69@gmx',
            "Driving your BMW down the road, is Fahrvergn\xC3\xBCgen",
            'Lorem ipsum',
            'From: cmbecker69@gmx.de'
        );
    }
}
