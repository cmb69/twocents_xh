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

namespace Twocents;

use PHPUnit\Framework\TestCase;

class PaginationTest extends TestCase
{
    /**
     * @dataProvider provideGatherPagesData
     * @var int $page
     * @var int $pageCount
     * @var int $radius
     */
    public function testGatherPages($page, $pageCount, $radius, array $expected)
    {
        global $plugin_cf;

        $plugin_cf['twocents']['pagination_radius'] = $radius;
        $subject = new Pagination($page, $pageCount);
        $this->assertEquals($expected, $subject->gatherPages());
    }

    /**
     * @return array
     */
    public function provideGatherPagesData()
    {
        return array(
            [1, 3, 2, [1, 2, 3]],
            [4, 7, 1, [1, null, 3, 4, 5, null, 7]]
        );
    }
}
