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

class DbTest extends TestCase
{
    public function testFindsAllTopics(): void
    {
        vfsStream::setup("root");
        mkdir(vfsStream::url("root/twocents/"));
        foreach (["foo", "bar", "baz"] as $name) {
            touch(vfsStream::url("root/twocents/$name.csv"));
        }
        $sut = new Db(vfsStream::url("root/twocents/"));
        $topics = $sut->findAllTopics();
        $this->assertEquals(["foo", "bar", "baz"], $topics);
    }
}
