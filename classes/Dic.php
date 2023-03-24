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

use Twocents\Infra\Captcha;
use Twocents\Infra\CsrfProtector;
use Twocents\Infra\Db;
use Twocents\Infra\FlashMessage;
use Twocents\Infra\HtmlCleaner;
use Twocents\Infra\Mailer;
use Twocents\Infra\Random;
use Twocents\Infra\SystemChecker;
use Twocents\Infra\View;

class Dic
{
    public static function makeMainController(): MainController
    {
        global $pth, $plugin_cf, $plugin_tx, $_XH_csrfProtection;
        static $instance = null;

        if ($instance === null) {
            $instance = new MainController(
                $pth["folder"]["plugins"] . "twocents/",
                $plugin_cf["twocents"],
                $plugin_tx["twocents"],
                isset($_XH_csrfProtection) ? new CsrfProtector : null,
                self::makeDb(),
                self::makeHtmlCleaner(),
                new Random,
                self::makeCaptcha(),
                new Mailer,
                self::makeView()
            );
        }
        return $instance;
    }

    public static function makeInfoController(): InfoController
    {
        global $pth;
        return new InfoController(
            $pth["folder"]["plugins"] . "twocents/",
            new SystemChecker,
            self::makeDb(),
            self::makeView()
        );
    }

    public static function testMakeMainAdminController(): MainAdminController
    {
        global $plugin_cf;

        return new MainAdminController(
            $plugin_cf["twocents"],
            new CsrfProtector,
            self::makeDb(),
            self::makeHtmlCleaner(),
            new FlashMessage,
            self::makeView()
        );
    }

    private static function makeDb(): Db
    {
        global $pth;

        return new Db($pth["folder"]["content"] . "twocents/");
    }

    private static function makeCaptcha(): Captcha
    {
        global $plugin_cf, $pth;

        return new Captcha(
            $pth["folder"]["plugins"],
            $plugin_cf["twocents"]["captcha_plugin"],
            defined("XH_ADM") && XH_ADM
        );
    }

    private static function makeHtmlCleaner(): HtmlCleaner
    {
        global $pth;

        return new HtmlCleaner($pth["folder"]["plugins"] . "twocents/");
    }

    private static function makeView(): View
    {
        global $pth, $plugin_tx;

        return new View($pth["folder"]["plugins"] . "twocents/views/", $plugin_tx["twocents"]);
    }
}
