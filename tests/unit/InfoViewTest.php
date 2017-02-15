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

class InfoViewTest extends TestCase
{
    /**
     * @var Controller
     */
    protected $subject;

    public function setUp()
    {
        global $twocents, $o, $pth, $plugin_tx;

        $this->defineConstant('XH_ADM', true);
        $this->defineConstant('TWOCENTS_VERSION', '1.0');
        $twocents = 'true';
        $o = '';
        $pth = array(
            'folder' => array('plugins' => './plugins/')
        );
        $plugin_tx = array(
            'twocents' => array('alt_icon' => 'Speech bubble')
        );
        $this->subject = new Controller();
        new PHPUnit_Extensions_MockFunction('XH_registerStandardPluginMenuItems', $this->subject);
        new PHPUnit_Extensions_MockFunction('print_plugin_admin', $this->subject);
        $this->subject->dispatch();
    }

    public function testRendersHeading()
    {
        global $o;

        @$this->assertTag(
            array(
                'tag' => 'h1',
                'content' => 'Twocents'
            ),
            $o
        );
    }

    public function testRendersIcon()
    {
        global $o;

        @$this->assertTag(
            array(
                'tag' => 'img',
                'attributes' => array(
                    'src' => './plugins/twocents/twocents.png',
                    'class' => 'twocents_icon',
                    'alt' => 'Speech bubble'
                )
            ),
            $o
        );
    }

    public function testRendersVersion()
    {
        global $o;

        @$this->assertTag(
            array(
                'tag' => 'p',
                'content' => 'Version: ' . TWOCENTS_VERSION
            ),
            $o
        );
    }

    public function testRendersCopyright()
    {
        global $o;

        @$this->assertTag(
            array(
                'tag' => 'p',
                'content' => "Copyright \xC2\xA9 2014",
                'child' => array(
                    'tag' => 'a',
                    'attributes' => array(
                        'href' => 'http://3-magi.net/',
                        'target' => '_blank'
                    ),
                    'content' => 'Christoph M. Becker'
                )
            ),
            $o
        );
    }

    public function testRendersLicense()
    {
        global $o;

        @$this->assertTag(
            array(
                'tag' => 'p',
                'attributes' => array('class' => 'twocents_license'),
                'content' => 'This program is free software:'
            ),
            $o
        );
    }
}
