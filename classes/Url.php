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

    /** @var array */
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
     * @param string $path
     * @param string $page
     */
    private function __construct($path, $page = '', array $params = [])
    {
       $this->path = $path;
       $this->page = $page;
       $this->params = $params;
    }

   /**
    * @param string $param
    * @param mixed $value
    * @return self
    */
    public function with($param, $value)
    {
        $params = $this->params;
        $params[$param] = $value;
        return new self($this->path, $this->page, $params);
    }

    /**
     * @param string $param
     * @return self
     */
    public function without($param)
    {
        $params = $this->params;
        unset($params[$param]);
        return new self($this->path, $this->page, $params);
    }

    /** @return string */
    public function __toString()
    {
        $result = $this->path;
        $queryString = $this->getQueryString();
        if ($queryString) {
            $result .= "?$queryString";
        }
        return $result;
    }

    /** @return string */
    public function getAbsolute()
    {
        $result = CMSIMPLE_URL;
        $queryString = $this->getQueryString();
        if ($queryString) {
            $result .= "?$queryString";
        }
        return $result;
    }

    /** @return string */
    private function getQueryString()
    {
        $result = "{$this->page}";
        $additional = preg_replace('/=(?=&|$)/', '', http_build_query($this->params, null, '&'));
        if ($additional) {
            $result .= "&$additional";
        }
        return $result;
    }
}
