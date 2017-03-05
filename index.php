<?php

/**
 * Copyright 2014-2017 Christoph M. Becker
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

/*
 * Prevent direct access and usage from unsupported CMSimple_XH versions.
 */
if (!defined('CMSIMPLE_XH_VERSION')
    || strpos(CMSIMPLE_XH_VERSION, 'CMSimple_XH') !== 0
    || version_compare(CMSIMPLE_XH_VERSION, 'CMSimple_XH 1.6', 'lt')
) {
    header('HTTP/1.1 403 Forbidden');
    header('Content-Type: text/plain; charset=UTF-8');
    die(<<<EOT
Twocents_XH detected an unsupported CMSimple_XH version.
Uninstall Twocents_XH or upgrade to a supported CMSimple_XH version!
EOT
    );
}

const TWOCENTS_VERSION = '@TWOCENTS_VERSION@';

/**
 * @param string $topicname
 * @return string
 */
function twocents($topicname)
{
    global $plugin_tx;

    try {
        $controller = new Twocents\MainController($topicname);
    } catch (DomainException $ex) {
        return XH_message('fail', $plugin_tx['twocents']['error_topicname']);
    }
    $action = Twocents\Router::getControllerAction($controller, 'twocents_action');
    ob_start();
    $controller->{$action}();
    return ob_get_clean();
}

$temp = new Twocents\Router();
$temp->route();
unset($temp);
