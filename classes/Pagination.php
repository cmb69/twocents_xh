<?php

/**
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

namespace Twocents;

class Pagination
{
    /**
     * @var int
     */
    private $itemCount;

    /**
     * @var int
     */
    private $page;

    /**
     * @var int
     */
    private $pageCount;

    /**
     * @var string
     */
    private $url;

    /**
     * @param int $itemCount
     * @param int $page
     * @param int $pageCount
     * @param string $url
     */
    public function __construct($itemCount, $page, $pageCount, $url)
    {
        $this->itemCount = (int) $itemCount;
        $this->page = (int) $page;
        $this->pageCount = (int) $pageCount;
        $this->url = (string) $url;
    }

    /**
     * @return string
     */
    public function render()
    {
        if ($this->pageCount <= 1) {
            return '';
        }
        $view = new View('pagination');
        $view->itemCount = $this->itemCount;
        $view->currentPage = $this->page;
        $view->pages = $this->gatherPages();
        $url = $this->url;
        $view->url = function ($page) use ($url) {
            return sprintf($url, $page);
        };
        return $view->render();
    }

    /**
     * @return ?int[]
     */
    private function gatherPages()
    {
        global $plugin_cf;

        $radius = $plugin_cf['twocents']['pagination_radius'];
        $pages = array(1);
        if ($this->page - $radius > 1 + 1) {
            $pages[] = null;
        }
        for ($i = $this->page - $radius; $i <= $this->page + $radius; $i++) {
            if ($i > 1 && $i < $this->pageCount) {
                $pages[] = $i;
            }
        }
        if ($this->page + $radius < $this->pageCount - 1) {
            $pages[] = null;
        }
        $pages[] = $this->pageCount;
        return $pages;
    }
}
