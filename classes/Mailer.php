<?php

/**
 * The service layer.
 *
 * PHP version 5
 *
 * @category  CMSimple_XH
 * @package   Twocents
 * @author    Peter Harteg <peter@harteg.dk>
 * @author    The CMSimple_XH developers <devs@cmsimple-xh.org>
 * @author    Christoph M. Becker <cmbecker69@gmx.de>
 * @copyright 1999-2009 <http://cmsimple.org/>
 * @copyright 2009-2014 The CMSimple_XH developers <http://cmsimple-xh.org/?The_Team>
 * @copyright 2014-2017 Christoph M. Becker <http://3-magi.net/>
 * @license   http://www.gnu.org/licenses/gpl-3.0.en.html GNU GPLv3
 * @link      http://3-magi.net/?CMSimple_XH/Twocents_XH
 */

namespace Twocents;

/**
 * The mailers.
 *
 * @category CMSimple_XH
 * @package  Twocents
 * @author   The CMSimple_XH developers <devs@cmsimple-xh.org>
 * @author   Christoph M. Becker <cmbecker69@gmx.de>
 * @license  http://www.gnu.org/licenses/gpl-3.0.en.html GNU GPLv3
 * @link     http://3-magi.net/?CMSimple_XH/Twocents_XH
 */
class Mailer
{
    /**
     * The line break characters.
     *
     * @var string
     */
    protected $lineBreak;

    /**
     * Makes and returns a new mailer.
     *
     * @param string $lineBreak A line break string.
     *
     * @return Mailer
     */
    public static function make($lineBreak = "\r\n")
    {
        return new self($lineBreak);
    }

    /**
     * Initializes a new instance.
     *
     * @param string $lineBreak A line break string.
     *
     * @return void
     */
    protected function __construct($lineBreak)
    {
        $this->lineBreak = (string) $lineBreak;
    }

    /**
     * Returns whether an email address is valid.
     *
     * For simplicity we are not aiming for full compliance with RFC 5322.
     * The local-part must be a dot-atom-text. The domain is checked with
     * gethostbyname() after applying idn_to_ascii(), if the latter is available.
     *
     * @param string $address An email address.
     *
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
        $local = $matches[1];
        $domain = $matches[2];
        if (function_exists('idn_to_ascii')) {
            $domain = defined('INTL_IDNA_VARIANT_UTS46')
                ? idn_to_ascii($domain, 0, INTL_IDNA_VARIANT_UTS46)
                : idn_to_ascii($domain);
        }
        if (gethostbyname($domain) == $domain) {
            return false;
        }
        return true;
    }

    /**
     * Sends a UTF-8 encoded mail.
     *
     * @param string $to                Receiver, or receivers of the mail.
     * @param string $subject           Subject of the email to be sent.
     * @param string $message           Message to be sent.
     * @param string $additionalHeaders String to be inserted at the end of the
     *                                  email header.
     *
     * @return bool Whether the mail was accepted for delivery.
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
        return mail($to, $subject, $message, $header);
    }

    /**
     * Returns the body of an email header field as "encoded word" (RFC 2047)
     * with "folding" (RFC 5322), if necessary.
     *
     * @param string $text The body of the MIME field.
     *
     * @return string
     *
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
            $func = create_function('$l', 'return \'=?UTF-8?B?\' . base64_encode($l) . \'?=\';');
            return implode($this->lineBreak . ' ', array_map($func, $lines));
        }
    }
}
