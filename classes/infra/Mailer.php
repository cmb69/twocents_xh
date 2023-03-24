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

class Mailer
{
    public function sendNotification(
        string $to,
        string $subject,
        string $attribution,
        string $message,
        string $replyTo
    ): bool {
        $body = $attribution . "\n\n> " . str_replace("\n", "\n> ", $message);
        $xhMail = $this->xhMail();
        $xhMail->setTo($to);
        $xhMail->setSubject($subject);
        $xhMail->setMessage($body);
        $xhMail->addHeader("From", $to);
        $xhMail->addHeader("Reply-To", $replyTo);
        return $xhMail->send();
    }

    /** @codeCoverageIgnore */
    protected function xhMail(): XhMail
    {
        return new XhMail();
    }
}
