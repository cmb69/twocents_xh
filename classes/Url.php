<?php

/**
 * Copyright (c)) Christoph M. Becker
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

class Url
{
    /** @var string */
    private $path;

    /** @var string */
    private $page;

    /** @var array<string,string> */
    private $params;

    public static function getCurrent(): self
    {
        global $sn, $su;

        if ($su) {
            $params = array_slice($_GET, 1);
        } else {
            $params = $_GET;
        }
        return new self($sn, $su, $params);
    }

    /**
     * @param array<string,string> $params
     */
    private function __construct(string $path, string $page = '', array $params = [])
    {
        $this->path = $path;
        $this->page = $page;
        $this->params = $params;
    }

    public function with(string $param, string $value): self
    {
        $params = $this->params;
        $params[$param] = $value;
        return new self($this->path, $this->page, $params);
    }

    public function without(string $param): self
    {
        $params = $this->params;
        unset($params[$param]);
        return new self($this->path, $this->page, $params);
    }

    public function __toString(): string
    {
        $result = $this->path;
        $queryString = $this->getQueryString();
        if ($queryString) {
            $result .= "?$queryString";
        }
        return $result;
    }

    public function getAbsolute(): string
    {
        $result = CMSIMPLE_URL;
        $queryString = $this->getQueryString();
        if ($queryString) {
            $result .= "?$queryString";
        }
        return $result;
    }

    private function getQueryString(): string
    {
        $result = "{$this->page}";
        $additional = preg_replace('/=(?=&|$)/', '', http_build_query($this->params, "", '&'));
        if ($additional) {
            $result .= "&$additional";
        }
        return $result;
    }
}
