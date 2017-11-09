<?php

/**
 * Copyright 2017 Christoph M. Becker
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

use PHPUnit\Framework\TestCase;

class UrlTest extends TestCase
{
    /**
     * @return void
     */
    public function testPathOnly()
    {
        $url = new Url('/');
        $this->assertEquals('/', $url->getRelative());
    }

    /**
     * @return void
     */
    public function testPathAndPageOnly()
    {
        $url = new Url('/', 'pagemanager');
        $this->assertEquals('/?pagemanager', $url->getRelative());
    }

    /**
     * @return void
     */
    public function testPathAndParamsOnly()
    {
        $url = new Url('/', '', ['foo' => 'bar']);
        $this->assertEquals('/?&foo=bar', $url->getRelative());
    }

    /**
     * @return void
     */
    public function testFullUrl()
    {
        $url = new Url('/', 'pagemanager', ['admin' => 'plugin_config', 'action' => 'plugin_edit', 'normal' => '']);
        $this->assertEquals('/?pagemanager&admin=plugin_config&action=plugin_edit&normal', $url->getRelative());
    }

    /**
     * @return void
     */
    public function testToString()
    {
        $url = new Url('/', 'foo', ['bar' => 'baz']);
        $this->assertEquals('/?foo&bar=baz', (string) $url);
    }

    /**
     * @return void
     */
    public function testComplexPage()
    {
        $url = new Url('/', 'S%C3%BCper/Lig');
        $this->assertEquals('/?S%C3%BCper/Lig', $url->getRelative());
    }

    /**
     * @return void
     */
    public function testAbsoulte()
    {
        uopz_redefine('CMSIMPLE_URL', 'http://example.com/');
        $url = new Url('/', 'foo', ['bar' => 'baz']);
        $this->assertEquals('http://example.com/?foo&bar=baz', $url->getAbsolute());
    }

    /**
     * @return void
     */
    public function testArrayParam()
    {
        $url = new Url('/', '', ['foo' => ['bar', 'baz']]);
        $this->assertEquals('/?&foo%5B0%5D=bar&foo%5B1%5D=baz', $url->getRelative());
    }

    /**
     * @return void
     */
    public function testWithAdds()
    {
        $url = new Url('/', '', ['foo' => 'bar']);
        $url = $url->with('baz', 'qux');
        $this->assertEquals('/?&foo=bar&baz=qux', $url->getRelative());
    }

    /**
     * @return void
     */
    public function testWithReplaces()
    {
        $url = new Url('/', '', ['foo' => 'bar']);
        $url = $url->with('foo', 'baz');
        $this->assertEquals('/?&foo=baz', $url->getRelative());
    }

    /**
     * @return void
     */
    public function testWithout()
    {
        $url = new Url('/', '', ['foo' => 'bar', 'baz' => 'qux']);
        $url = $url->without('foo');
        $this->assertEquals('/?&baz=qux', $url->getRelative());
    }

    /**
     * @return void
     */
    public function testGetCurrentWithPage()
    {
        global $sn, $su;

        $sn = '/';
        $su = 'foo';
        $_GET = ['foo' => '', 'bar' => 'baz'];
        $url = Url::getCurrent();
        $this->assertEquals('/?foo&bar=baz', $url->getRelative());
    }

    /**
     * @return void
     */
    public function testGetCurrentWithoutPage()
    {
        global $sn, $su;

        $sn = '/';
        $su = '';
        $_GET = ['bar' => 'baz'];
        $url = Url::getCurrent();
        $this->assertEquals('/?&bar=baz', $url->getRelative());
    }
}
