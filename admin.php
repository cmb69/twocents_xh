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

use Plib\Request;
use Twocents\Dic;
use Twocents\Infra\Responder;

/**
 * @var string $admin
 * @var string $o
 */

XH_registerStandardPluginMenuItems(true);

if (XH_wantsPluginAdministration('twocents')) {
    $o .= print_plugin_admin('on');
    switch ($admin) {
        case '':
            $o .= Dic::makeInfoController()()();
            break;
        case 'plugin_main':
            $o .= Dic::testMakeMainAdminController()(Request::current())();
            break;
        default:
            $o .= plugin_admin_common();
    }
}
