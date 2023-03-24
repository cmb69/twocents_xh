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

namespace Twocents;

use ApprovalTests\Approvals;
use PHPUnit\Framework\TestCase;
use Twocents\Infra\FakeDb;
use Twocents\Infra\FakeRequest;
use Twocents\Infra\FakeSystemChecker;
use Twocents\Infra\View;

class InfoControllerTest extends TestCase
{
    public function testRendersPluginInfo(): void
    {
        $sut = new InfoController(
            "./plugins/twocents/",
            new FakeSystemChecker,
            new FakeDb,
            new View("./views/", XH_includeVar("./languages/en.php", "plugin_tx")["twocents"])
        );
        $response = $sut(new FakeRequest());
        $this->assertEquals("Twocents 1.0", $response->title());
        Approvals::verifyHtml($response->output());
    }
}
