<?php

/**
 * Copyright 2023 Christoph M. Becker
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

namespace Twocents\Infra;

use XH\Mail as XhMail;

class FakeMailer extends Mailer
{
    public $output = [];
    public $sent = false;
    protected function xhMail(): XhMail
    {
        return new class($this) extends XhMail {
            private $wrapper;
            public function __construct(FakeMailer $wrapper)
            {
                $this->wrapper = $wrapper;
            }
            public function setTo($to)
            {
                $this->wrapper->output["to"] = $to;
            }
            public function setSubject($subject)
            {
                $this->wrapper->output["subject"] = $subject;
            }
            public function setMessage($message)
            {
                $this->wrapper->output["message"] = $message;
            }
            public function addHeader($name, $value)
            {
                $this->wrapper->output["header"][$name] = $value;
            }
            public function send()
            {
                return $this->wrapper->sent = true;
            }
        };
    }
}
