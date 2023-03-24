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
    private $text;

    /** @param array<string,string> $text */
    public function __construct(string $viewFolder, array $text)
    {
        $this->viewFolder = $viewFolder;
        $this->text = $text;
    }

    /** @param scalar $args */
    public function text(string $key, ...$args): string
    {
        return $this->esc(sprintf($this->text[$key], ...$args));
    }

    /** @param scalar $args */
    public function plural(string $key, int $count, ...$args): string
    {
        $suffix = $count == 0 ? "_0" : "_1";
        return $this->esc(sprintf($this->text["{$key}{$suffix}"], $count, ...$args));
    }

    /** @param scalar $args */
    public function message(string $type, string $key, ...$args): string
    {
        return XH_message($type, $this->text[$key], ...$args);
    }

    /** @param array<string,mixed> $_data */
    public function render(string $_template, array $_data): string
    {
        extract($_data);
        ob_start();
        include "{$this->viewFolder}{$_template}.php";
        return (string) ob_get_clean();
    }

    /** @param mixed $value */
    public function renderMeta(string $name, $value): string
    {
        $name = $this->esc($name);
        $value = json_encode($value, JSON_HEX_APOS|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
        return "<meta name=\"$name\" content='$value'>\n";
    }

    public function renderScript(string $filename): string
    {
        $filename = $this->esc($filename);
        return "<script src=\"$filename\"></script>\n";
    }

    /** @param scalar $value */
    public function esc($value): string
    {
        return XH_hsc((string) $value);
    }

    public function raw(string $string): string
    {
        return $string;
    }
}
