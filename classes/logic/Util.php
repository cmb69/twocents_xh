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

namespace Twocents\Logic;

use Twocents\Value\Comment;

class Util
{
    public static function htmlify(string $text): string
    {
        return (string) preg_replace(
            array('/(?:\r\n|\r)/', '/\n{2,}/', '/\n/'),
            array("\n", '</p><p>', "<br>"),
            '<p>' . $text . '</p>'
        );
    }

    public static function plainify(string $html): string
    {
        return html_entity_decode(
            strip_tags(
                str_replace(
                    array('</p><p>', "<br>"),
                    array(PHP_EOL . PHP_EOL, PHP_EOL),
                    $html
                )
            ),
            ENT_QUOTES,
            'UTF-8'
        );
    }

    /** @return list<string> */
    public static function validateComment(Comment $comment): array
    {
        $result = [];
        if (utf8_strlen($comment->user()) < 2) {
            $result[] = "error_user";
        }
        if (!Util::isValidEmailAddress($comment->email())) {
            $result[] = "error_email";
        }
        if (utf8_strlen($comment->message()) < 2) {
            $result[] = "error_message";
        }
        return $result;
    }

    public static function isValidEmailAddress(string $email): bool
    {
        return (bool) preg_match(
            '/^[a-zA-Z0-9.!#$%&\'*+\/=?^_`{|}~-]+@[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}'
            . '[a-zA-Z0-9])?(?:\.[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?)*$/',
            $email
        );
    }

    public static function encodeBase64url(string $string): string
    {
        assert(strlen($string) % 3 === 0);
        return str_replace(["+", "/"], ["-", "_"], base64_encode($string));
    }
}
