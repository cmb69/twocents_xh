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

namespace Twocents\Logic;

use PHPUnit\Framework\TestCase;
use Twocents\Value\Comment;

class UtilTest extends TestCase
{
    /** @dataProvider comments */
    public function testCommentValidation(Comment $comment, array $expected): void
    {
        $res = Util::validateComment($comment);
        $this->assertEquals($expected, $res);
    }

    public function comments(): array
    {
        return [
            [new Comment(null, "", 0, "\x80", "cmb@example.com", "blah", false), ["error_user"]],
            [new Comment(null, "", 0, "foo\x0abar", "cmb@example.com", "blah", false), ["error_user"]],
            [new Comment(null, "", 0, "cmb", "\x80", "blah", false), ["error_email"]],
            [new Comment(null, "", 0, "cmb", "c m b@example.com", "blah", false), ["error_email"]],
            [new Comment(null, "", 0, "cmb", "cmb@example.com", "\x80", false), ["error_message"]],
            [new Comment(null, "", 0, "cmb", "cmb@example.com", "blah\x00", false), ["error_message"]],
        ];
    }
}
