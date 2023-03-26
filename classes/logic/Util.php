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
        if (!self::validateUser($comment->user())) {
            $result[] = "error_user";
        }
        if (!self::validateEmail($comment->email())) {
            $result[] = "error_email";
        }
        if (!self::validateMessage($comment->message())) {
            $result[] = "error_message";
        }
        return $result;
    }

    private static function validateUser(string $user): bool
    {
        if (!utf8_is_valid($user)) {
            return false;
        }
        $len = utf8_strlen($user);
        if ($len < 2 || $len > 100) {
            return false;
        }
        if (!preg_match('/^[[:print:]]+$/u', $user)) {
            return false;
        }
        return true;
    }

    private static function validateEmail(string $email): bool
    {
        if (!utf8_is_valid($email)) {
            return false;
        }
        $len = utf8_strlen($email);
        if ($len < 2 || $len > 100) {
            return false;
        }
        if (!self::isValidEmailAddress($email)) {
            return false;
        }
        return true;
    }

    private static function isValidEmailAddress(string $email): bool
    {
        // <https://html.spec.whatwg.org/multipage/input.html#valid-e-mail-address>
        $local = '[a-zA-Z0-9.!#$%&\'*+\/=?^_`{|}~-]+';
        $label = '[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?';
        return (bool) preg_match("/^$local@$label(?:\\.$label)*$/u", $email);
    }

    private static function validateMessage(string $message): bool
    {
        if (!utf8_is_valid($message)) {
            return false;
        }
        $len = utf8_strlen($message);
        if ($len < 2 || $len > 2000) {
            return false;
        }
        if (!preg_match('/^[[:print:]\x0a\x0d]+$/u', $message)) {
            return false;
        }
        return true;
    }

    public static function encodeBase64url(string $string): string
    {
        assert(strlen($string) % 3 === 0);
        return str_replace(["+", "/"], ["-", "_"], base64_encode($string));
    }

    /**
     * @param list<Comment> $comments
     * @return array{list<Comment>,int,int,int}
     */
    public static function limitComments(array $comments, int $limit, int $page, int $order)
    {
        usort($comments, function ($a, $b) use ($order) {
            return ($a->time() <=> $b->time()) * $order;
        });
        $count = count($comments);
        $pageCount = (int) ceil($count / $limit);
        $page = max(1, min($pageCount, $page));
        $comments = array_splice($comments, ($page - 1) * $limit, $limit);
        return [$comments, $count, $page, $pageCount];
    }
}
