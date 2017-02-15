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

use PHPUnit_Extensions_MockFunction;

class AdministrationTest extends TestCase
{
    /**
     * @var Controller
     */
    protected $subject;

    /**
     * @var object
     */
    protected $rspmiMock;

    public function setUp()
    {
        global $twocents, $admin, $action;

        $this->defineConstant('XH_ADM', true);
        $twocents = 'true';
        $admin = 'plugin_stylesheet';
        $action = 'plugin_text';
        $this->subject = new Controller();
        $this->rspmiMock = new PHPUnit_Extensions_MockFunction('XH_registerStandardPluginMenuItems', $this->subject);
        $printPluginAdminMock = new PHPUnit_Extensions_MockFunction('print_plugin_admin', $this->subject);
        $printPluginAdminMock->expects($this->once())->with('on');
        $pluginAdminCommonMock = new PHPUnit_Extensions_MockFunction('plugin_admin_common', $this->subject);
        $pluginAdminCommonMock->expects($this->once())
            ->with($action, $admin, 'twocents');
    }

    public function testShowsIntegratedPluginMenu()
    {
        $this->rspmiMock->expects($this->once())->with(true);
        $this->subject->dispatch();
    }

    public function testStylesheet()
    {
        $this->subject->dispatch();
    }
}
