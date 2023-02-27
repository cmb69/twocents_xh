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

use Twocents\Infra\Captcha;
use Twocents\Infra\CsrfProtector;
use Twocents\Infra\Db;
use Twocents\Infra\HtmlCleaner;
use Twocents\Infra\View;
use Twocents\Infra\Request;
use XH\Mail as Mailer;

if (!defined('CMSIMPLE_XH_VERSION')) {
    header("HTTP/1.1 403 Forbidden");
    exit;
}

/**
 * @param string $topicname
 * @param bool $readonly
 * @return string
 */
function twocents($topicname, $readonly = false)
{
    global $pth, $plugin_cf, $plugin_tx, $_XH_csrfProtection;

    try {
        $controller = new Twocents\MainController(
            $pth['folder']['plugins'],
            $plugin_cf['twocents'],
            $plugin_tx['twocents'],
            isset($_XH_csrfProtection) ? new CsrfProtector : null,
            new Db($pth['folder']['content'] . 'twocents/'),
            new HtmlCleaner($pth["folder"]["plugins"] . "twocents/"),
            new Captcha(
                $pth["folder"]["plugins"],
                $plugin_cf["twocents"]["captcha_plugin"],
                defined("XH_ADM") && XH_ADM
            ),
            new Mailer,
            new View($pth["folder"]["plugins"] . "twocents/views/", $plugin_tx["twocents"]),
            $topicname,
            $readonly
        );
    } catch (DomainException $ex) {
        return XH_message('fail', $plugin_tx['twocents']['error_topicname']);
    }
    $action = Twocents\Plugin::getControllerAction($controller, 'twocents_action');
    ob_start();
    $response = $controller->{$action}(new Request);
    if ($response) {
        $response->fire();
    }
    return (string) ob_get_clean();
}

(new Twocents\Plugin())->run();
