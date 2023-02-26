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

namespace Twocents\Infra;

class View
{
    /** @var string */
    private $viewFolder;

    /** @var array<string,string> */
    private $lang;

    /**
     * @param array<string,string> $lang
     */
    public function __construct(string $viewFolder, array $lang)
    {
        $this->viewFolder = $viewFolder;
        $this->lang = $lang;
    }

    public function text(string $key): string
    {
        $args = func_get_args();
        array_shift($args);
        return vsprintf($this->lang[$key], $args);
    }

    public function plural(string $key, int $count): string
    {
        $suffix = $count == 0 ? "_0" : "_1";
        $args = func_get_args();
        array_shift($args);
        return vsprintf($this->lang["{$key}{$suffix}"], $args);
    }

    /**
     * @param array<string,mixed> $_data
     */
    public function render(string $_template, array $_data): string
    {
        extract($_data);
        ob_start();
        include "{$this->viewFolder}{$_template}.php";
        return (string) ob_get_clean();
    }
}
