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
    public function route()
    {
        global $twocents;

        if (XH_ADM) {
            if (function_exists('XH_registerStandardPluginMenuItems')) {
                XH_registerStandardPluginMenuItems(true);
            }
            if (isset($twocents) && $twocents == 'true') {
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
        return $view;
    }

    /**
     * @return string
     */
    private function handleMainAdministration()
    {
        $controller = new MainAdminController();
        return '<h1>Twocents &ndash; Conversion</h1>'
            . $controller->{$this->getMainAdminAction()}();
    }

    /**
     * @return string
     */
    private function getMainAdminAction()
    {
        global $action;

        switch ($action) {
            case 'convert_html':
                return 'convertToHtmlAction';
            case 'convert_plain':
                return 'convertToPlainTextAction';
            case 'import_comments':
                return 'importCommentsAction';
            case 'import_gbook':
                return 'importGbookAction';
            default:
                return 'defaultAction';
        }
    }
}
