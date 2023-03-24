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

use Twocents\Value\Html;

class View
{
    /** @var string */
    private $templateFolder;

    /** @var array<string,string> */
    private $text;

    /** @param array<string,string> $text */
    public function __construct(string $templateFolder, array $text)
    {
        $this->templateFolder = $templateFolder;
        $this->text = $text;
    }

    /** @param scalar $args */
    public function plain(string $key, ...$args): string
    {
        return sprintf($this->text[$key], ...$args);
    }

    /** @param scalar $args */
    public function text(string $key, ...$args): string
    {
        return sprintf($this->esc($this->text[$key]), ...$args);
    }

    /** @param scalar $args */
    public function plural(string $key, int $count, ...$args): string
    {
        $suffix = $count === 0 ? "_0" : XH_numberSuffix($count);
        return sprintf($this->esc($this->text[$key . $suffix]), $count, ...$args);
    }

    /** @param scalar $args */
    public function message(string $type, string $key, ...$args): string
    {
        return XH_message($type, $this->text[$key], ...$args);
    }

    /** @param scalar $args */
    public function pmessage(string $type, string $key, int $count, ...$args): string
    {
        $suffix = $count === 0 ? "_0" : XH_numberSuffix($count);
        return XH_message($type, $this->text[$key . $suffix], $count, ...$args);
    }

    /** @param array<string,mixed> $_data */
    public function render(string $_template, array $_data): string
    {
        array_walk_recursive($_data, function (&$value) {
            assert(is_null($value) || is_scalar($value) || $value instanceof Html);
            if (is_string($value)) {
                $value = $this->esc($value);
            } elseif ($value instanceof Html) {
                $value = $value->toString();
            }
        });
        extract($_data);
        ob_start();
        include $this->templateFolder . $_template . ".php";
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

    public function esc(string $string): string
    {
        return XH_hsc($string);
    }
}
