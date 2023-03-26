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

class Captcha
{
    /** @var string */
    private $pluginsFolder;

    /** @var string */
    private $captchaPlugin;

    public function __construct(string $pluginsFolder, string $captchaPlugin)
    {
        $this->pluginsFolder = $pluginsFolder;
        $this->captchaPlugin = $captchaPlugin;
    }

    public function render(bool $admin): string
    {
        $filename = $this->pluginsFolder . $this->captchaPlugin . "/captcha.php";
        if (!$admin && $this->captchaPlugin && is_readable($filename)) {
            include_once $filename;
            $func = $this->captchaPlugin . "_captcha_display";
            if (is_callable($func)) {
                return $func();
            }
        }
        return "";
    }

    public function check(bool $admin): bool
    {
        $filename = $this->pluginsFolder . $this->captchaPlugin . "/captcha.php";
        if (!$admin && $this->captchaPlugin && is_readable($filename)) {
            include_once $filename;
            $func = $this->captchaPlugin . "_captcha_check";
            if (is_callable($func) && !$func()) {
                return false;
            }
        }
        return true;
    }
}
