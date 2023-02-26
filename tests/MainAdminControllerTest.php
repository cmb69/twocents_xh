<?php

/**
 * Copyright (c) Christoph M. Becker
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
use Twocents\Infra\Db;
use Twocents\Infra\HtmlCleaner;
use Twocents\Infra\View;
use XH\CSRFProtection as CsrfProtector;

class MainAdminControllerTest extends TestCase
{
    public function testDefaultActionRendersConversionOverview(): void
    {
        $plugin_cf = XH_includeVar("./config/config.php", 'plugin_cf');
        $conf = $plugin_cf['twocents'];
        $plugin_tx = XH_includeVar("./languages/en.php", 'plugin_tx');
        $lang = $plugin_tx['twocents'];
        $csrfProtector = $this->createStub(CsrfProtector::class);
        $htmlCleaner = $this->createStub(HtmlCleaner::class);
        $view = new View("./views/", $lang);
        $sut = new MainAdminController("/", $conf, $csrfProtector, new Db("./"), $htmlCleaner, $view);
        $response = $sut->defaultAction();
        Approvals::verifyHtml($response);
    }
}
