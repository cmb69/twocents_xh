<?php

/**
 * Copyright 2017-2023 Christoph M. Becker
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

class SpamFilterTest extends TestCase
{
    /** @var SpamFilter */
    private $subject;

    public function setUp(): void
    {
        $this->subject = new SpamFilter('porn,viagra');
    }

    /**
     * @dataProvider provideIsSpamData
     */
    public function testIsSpam(string $message, bool $expected)
    {
        $this->assertSame($expected, $this->subject->isSpam($message));
    }

    public function provideIsSpamData(): array
    {
        return array(
            ['this is no spam', false],
            ['this is porn spam', true],
            ['this is ViAgRa spam', true]
        );
    }
}
