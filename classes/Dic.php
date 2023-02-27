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

use Twocents\Infra\CsrfProtector;
use Twocents\Infra\Db;
use Twocents\Infra\HtmlCleaner;
use Twocents\Infra\SystemChecker;
use Twocents\Infra\SystemCheckService;
use Twocents\Infra\View;

class Dic
{
    public static function makeInfoController(): InfoController
    {
        global $pth, $plugin_tx;

        return new InfoController(
            new SystemCheckService(
                $pth['folder']['plugins'],
                $plugin_tx['twocents'],
                "{$pth['folder']['base']}content/twocents/",
                new SystemChecker()
            ),
            self::makeView()
        );
    }

    public static function testMakeMainAdminController(): MainAdminController
    {
        global $pth, $sn, $plugin_cf;

        return new MainAdminController(
            $sn,
            $plugin_cf['twocents'],
            new CsrfProtector,
            new Db($pth['folder']['content'] . 'twocents/'),
            new HtmlCleaner($pth["folder"]["plugins"] . "twocents/"),
            self::makeView()
        );
    }

    private static function makeView(): View
    {
        global $pth, $plugin_tx;

        return new View($pth["folder"]["plugins"] . "twocents/views/", $plugin_tx["twocents"]);
    }
}
