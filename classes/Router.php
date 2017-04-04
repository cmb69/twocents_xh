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

namespace Twocents;

class Router
{
    /**
     * @param string $param
     * @return string
     */
    public static function getControllerAction(Controller $controller, $param)
    {
        $action = preg_replace_callback(
            '/_([a-z])/',
            function ($matches) {
                return ucfirst($matches[1]);
            },
            isset($_POST[$param]) ? stsl($_POST[$param]) : 'default'
        );
        if (!method_exists($controller, "{$action}Action")) {
            $action = 'default';
        }
        return "{$action}Action";
    }

    public function route()
    {
        if (XH_ADM) {
            if (function_exists('XH_registerStandardPluginMenuItems')) {
                XH_registerStandardPluginMenuItems(true);
            }
            if (XH_wantsPluginAdministration('twocents')) {
                $this->handleAdministration();
            }
        }
    }

    protected function handleAdministration()
    {
        global $admin, $action, $o;

        $o .= print_plugin_admin('on');
        switch ($admin) {
            case '':
                $o .= $this->renderInfo();
                break;
            case 'plugin_main':
                $o .= $this->handleMainAdministration();
                break;
            default:
                $o .= plugin_admin_common($action, $admin, 'twocents');
        }
    }

    /**
     * @return View
     */
    protected function renderInfo()
    {
        global $pth;

        $view = new View('info');
        $view->logo = "{$pth['folder']['plugins']}twocents/twocents.png";
        $view->version = TWOCENTS_VERSION;
        $view->checks = (new SystemCheckService)->getChecks();
        return $view;
    }

    /**
     * @return string
     */
    private function handleMainAdministration()
    {
        $controller = new MainAdminController();
        ob_start();
        $controller->{self::getControllerAction($controller, 'action')}();
        return ob_get_clean();
    }
}
