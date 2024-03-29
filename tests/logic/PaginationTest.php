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

class PaginationTest extends TestCase
{
    /**
     * @dataProvider provideGatherPagesData
     */
    public function testGatherPages(int $page, int $pageCount, int $radius, array $expected)
    {
        $subject = new Pagination($page, $pageCount, $radius);
        $this->assertEquals($expected, $subject->gatherPages());
    }

    public function provideGatherPagesData(): array
    {
        return array(
            [1, 3, 2, [1, 2, 3]],
            [4, 7, 1, [1, null, 3, 4, 5, null, 7]]
        );
    }
}
