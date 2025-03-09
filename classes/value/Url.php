<?php

/**
 * Copyright (c) Christoph M. Becker
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

namespace Twocents\Value;

class Url
{
    public static function from(string $url): self
    {
        $that = new self;
        $parts = parse_url($url);
        assert(isset($parts["scheme"], $parts["host"], $parts["path"]));
        $that->base = $parts["scheme"] . "://" . $parts["host"];
        $that->path = $parts["path"];
        $match = preg_match('/^([^=&]*)(?:&|$)(.*)/', $parts["query"] ?? "", $matches);
        assert($match !== false);
        $that->page = $matches[1]; // @phpstan-ignore-line
        parse_str($matches[2], $that->params); // @phpstan-ignore-line
        return $that;
    }

    /** @var string */
    private $base;

    /** @var string */
    private $path;

    /** @var string */
    private $page;

    /** @var array<string,string> */
    private $params;

    /** @return string|array<string>|null */
    public function param(string $key)
    {
        return $this->params[$key] ?? null;
    }

    public function with(string $param, string $value): self
    {
        $that = clone $this;
        $that->params[$param] = $value;
        return $that;
    }

    public function without(string $param): self
    {
        $that = clone $this;
        unset($that->params[$param]);
        return $that;
    }

    public function relative(): string
    {
        $query = $this->queryString();
        if ($query === "") {
            return $this->path;
        }
        return $this->path . "?" . $query;
    }

    public function absolute(): string
    {
        $query = $this->queryString();
        if ($query === "") {
            return $this->base . $this->path;
        }
        return $this->base . $this->path . "?" . $query;
    }

    private function queryString(): string
    {
        $query = preg_replace('/=(?=&|$)/', "", http_build_query($this->params, "", "&"));
        if ($query === "") {
            return $this->page;
        }
        return $this->page . "&" . $query;
    }
}
