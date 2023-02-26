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

class Plugin
{
    const VERSION = "1.0";

    /** @param object $controller */
    public static function getControllerAction($controller, string $param): string
    {
        $action = preg_replace_callback(
            '/_([a-z])/',
            function ($matches) {
                return ucfirst($matches[1]);
            },
            isset($_POST[$param]) ? $_POST[$param] : 'default'
        );
        if (!method_exists($controller, "{$action}Action")) {
            $action = 'default';
        }
        return "{$action}Action";
    }

    /** @return void */
    public function run()
    {
        if (defined('XH_ADM') && XH_ADM) {
            XH_registerStandardPluginMenuItems(true);
            if (XH_wantsPluginAdministration('twocents')) {
                $this->handleAdministration();
            }
        }
    }

    /** @return void */
    protected function handleAdministration()
    {
        global $admin, $o;

        $o .= print_plugin_admin('on');
        switch ($admin) {
            case '':
                $o .= $this->renderInfo();
                break;
            case 'plugin_main':
                $o .= $this->handleMainAdministration();
                break;
            default:
                $o .= plugin_admin_common();
        }
    }

    protected function renderInfo(): string
    {
        global $pth, $plugin_tx;

        $systemCheckService = new SystemCheckService(
            $pth['folder']['plugins'],
            $plugin_tx['twocents'],
            "{$pth['folder']['base']}content/twocents/",
            new SystemChecker()
        );
        $view = new View("{$pth['folder']['plugins']}twocents/views/", $plugin_tx['twocents']);
        return $view->render('info', [
            'logo' => "{$pth['folder']['plugins']}twocents/twocents.png",
            'version' => Plugin::VERSION,
            'checks' => $systemCheckService->getChecks(),
        ]);
    }

    private function handleMainAdministration(): string
    {
        global $pth, $sn, $plugin_cf, $plugin_tx, $_XH_csrfProtection;

        $controller = new MainAdminController(
            "{$pth['folder']['plugins']}twocents/",
            $sn,
            $plugin_cf['twocents'],
            $plugin_tx['twocents'],
            isset($_XH_csrfProtection) ? $_XH_csrfProtection : null
        );
        return $controller->{self::getControllerAction($controller, 'action')}();
    }
}
