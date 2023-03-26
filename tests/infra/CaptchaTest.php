<?php

/**
 * Copyright 2023 Christoph M. Becker
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

namespace Twocents\Infra;

use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;

class CaptchaTest extends TestCase
{
    private const CAPTCHA = <<<EOS
    <?php
    function fake_captcha_display() {
        return "<p>This is not a real CAPTCHA!</p>";
    }
    function fake_captcha_check() {
        return false;
    }
    EOS;

    public function testRendersCaptcha(): void
    {
        vfsStream::setup("root");
        mkdir("vfs://root/fake", 0777, true);
        file_put_contents("vfs://root/fake/captcha.php", self::CAPTCHA);
        $sut = new Captcha("vfs://root/", "fake");
        $result = $sut->render(false);
        $this->assertEquals("<p>This is not a real CAPTCHA!</p>", $result);
    }

    public function testRendersNoCaptchaForAdmin(): void
    {
        vfsStream::setup("root");
        mkdir("vfs://root/fake", 0777, true);
        file_put_contents("vfs://root/fake/captcha.php", self::CAPTCHA);
        $sut = new Captcha("vfs://root/", "fake");
        $result = $sut->render(true);
        $this->assertEquals("", $result);
    }

    public function testChecksCaptcha(): void
    {
        vfsStream::setup("root");
        mkdir("vfs://root/fake", 0777, true);
        file_put_contents("vfs://root/fake/captcha.php", self::CAPTCHA);
        $sut = new Captcha("vfs://root/", "fake");
        $result = $sut->check(false);
        $this->assertFalse($result);
    }

    public function testCaptchaCheckSucceedsForAdmin(): void
    {
        vfsStream::setup("root");
        mkdir("vfs://root/fake", 0777, true);
        file_put_contents("vfs://root/fake/captcha.php", self::CAPTCHA);
        $sut = new Captcha("vfs://root/", "fake");
        $result = $sut->check(true);
        $this->assertTrue($result);
    }
}
