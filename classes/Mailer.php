<?php

/**
 * Copyright 1999-2009 Gert Ebersbach
 * Copyright 2009-2014 The CMSimple_XH developers
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
/**
 * @copyright 1999-2009 <http://cmsimple.org/>
 * @copyright 2009-2014 The CMSimple_XH developers <http://cmsimple-xh.org/?The_Team>
 * @copyright 2014-2017 Christoph M. Becker <http://3-magi.net/>
 * @license   http://www.gnu.org/licenses/gpl-3.0.en.html GNU GPLv3
 */

namespace Twocents;

class Mailer
{
    /** @var MailHelper */
    private $mailHelper;

    /**
     * @var string
     */
    protected $lineBreak;

    /**
     * @param string $lineBreak
     */
    public function __construct(MailHelper $mailHelper, $lineBreak = "\r\n")
    {
        $this->mailHelper = $mailHelper;
        $this->lineBreak = (string) $lineBreak;
    }

    /**
     * Returns whether an email address is valid.
     *
     * For simplicity we are not aiming for full compliance with RFC 5322.
     * The local-part must be a dot-atom-text. The domain is checked with
     * gethostbyname() after applying idn_to_ascii(), if the latter is available.
     *
     * @param string $address
     * @return bool
     */
    public function isValidAddress($address)
    {
        $atext = '[!#-\'*+\-\/-9=?A-Z^-~]';
        $dotAtomText = $atext . '(?:' . $atext . '|\.)*';
        $pattern = '/^(' . $dotAtomText . ')@([^@]+)$/u';
        if (!preg_match($pattern, $address, $matches)) {
            return false;
        }
        $domain = $matches[2];
        if (function_exists('idn_to_ascii')) {
            $domain = defined('INTL_IDNA_VARIANT_UTS46')
                ? idn_to_ascii($domain, 0, INTL_IDNA_VARIANT_UTS46)
                : idn_to_ascii($domain);
        }
        if ($this->mailHelper->gethostbyname($domain) == $domain) {
            return false;
        }
        return true;
    }

    /**
     * @param string $to
     * @param string $subject
     * @param string $message
     * @param string $additionalHeaders
     * @return bool
     */
    public function send($to, $subject, $message, $additionalHeaders = '')
    {
        $header = 'MIME-Version: 1.0' . $this->lineBreak
            . 'Content-Type: text/plain; charset=UTF-8; format=flowed'
            . $this->lineBreak
            . 'Content-Transfer-Encoding: base64' . $this->lineBreak
            . $additionalHeaders;
        $subject = $this->encodeMIMEFieldBody($subject);
        $message = preg_replace('/(?:\r\n|\r|\n)/', $this->lineBreak, trim($message));
        $message = chunk_split(base64_encode($message));
        return $this->mailHelper->mail($to, $subject, $message, $header);
    }

    /**
     * Returns the body of an email header field as "encoded word" (RFC 2047)
     * with "folding" (RFC 5322), if necessary.
     *
     * @param string $text
     * @return string
     * @todo Don't we have to fold overlong pure ASCII texts also?
     */
    protected function encodeMIMEFieldBody($text)
    {
        if (!preg_match('/(?:[^\x00-\x7F])/', $text)) { // ASCII only
            return $text;
        } else {
            $lines = array();
            do {
                $i = 45;
                if (strlen($text) > $i) {
                    while ((ord($text[$i]) & 0xc0) == 0x80) {
                        $i--;
                    }
                    $lines[] = substr($text, 0, $i);
                    $text = substr($text, $i);
                } else {
                    $lines[] = $text;
                    $text = '';
                }
            } while ($text != '');
            $func = function ($l) {
                return '=?UTF-8?B?' . base64_encode($l) . '?=';
            };
            return implode($this->lineBreak . ' ', array_map($func, $lines));
        }
    }
}
