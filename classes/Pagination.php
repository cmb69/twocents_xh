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

namespace Twocents;

class Pagination
{
    /** @var int */
    private $page;

    /** @var int */
    private $pageCount;

    /** @var int */
    private $radius;

    public function __construct(int $page, int $pageCount)
    {
        global $plugin_cf;

        $this->page = (int) $page;
        $this->pageCount = (int) $pageCount;
        $this->radius = $plugin_cf['twocents']['pagination_radius'];
    }

    /** @return array<int|null> */
    public function gatherPages(): array
    {
        $pages = array(1);
        if ($this->page - $this->radius > 1 + 1) {
            $pages[] = null;
        }
        for ($i = $this->page - $this->radius; $i <= $this->page + $this->radius; $i++) {
            if ($i > 1 && $i < $this->pageCount) {
                $pages[] = $i;
            }
        }
        if ($this->page + $this->radius < $this->pageCount - 1) {
            $pages[] = null;
        }
        $pages[] = $this->pageCount;
        return $pages;
    }
}
