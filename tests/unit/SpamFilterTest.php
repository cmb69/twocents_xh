<?php

/**
 * Copyright 2017 Christoph M. Becker
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

class SpamFilterTest extends TestCase
{
    /**
     * @var SpamFilter
     */
    private $subject;

    public function setUp()
    {
        global $plugin_tx;

        $plugin_tx['twocents']['spam_words'] = 'porn,viagra';
        $this->subject = new SpamFilter;
    }

    /**
     * @dataProvider provideIsSpamData
     * @param string $message
     * @param bool $expected
     */
    public function testIsSpam($message, $expected)
    {
        $this->assertSame($expected, $this->subject->isSpam($message));
    }

    /**
     * @return array
     */
    public function provideIsSpamData()
    {
        return array(
            ['this is no spam', false],
            ['this is porn spam', true],
            ['this is ViAgRa spam', true]
        );
    }
}
