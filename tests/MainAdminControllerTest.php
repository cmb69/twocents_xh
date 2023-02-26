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
use Twocents\Infra\FakeCsrfProtector;
use Twocents\Infra\HtmlCleaner;
use Twocents\Infra\View;
use Twocents\Value\Comment;

class MainAdminControllerTest extends TestCase
{
    public function testRendersOverview(): void
    {
        $sut = $this->sut();
        $response = $sut->defaultAction();
        Approvals::verifyHtml($response);
    }

    public function testRendersOverviewForHtml(): void
    {
        $sut = $this->sut(["conf" => ["comments_markup" => "HTML"]]);
        $response = $sut->defaultAction();
        Approvals::verifyHtml($response);
    }

    public function testConvertsToHtml()
    {
        $csrfProtector = new FakeCsrfProtector;
        $sut = $this->sut(["csrfProtector" => $csrfProtector]);
        $response = $sut->convertToHtmlAction();
        $this->assertTrue($csrfProtector->hasChecked());
        Approvals::verifyHtml($response);
    }

    public function testConvertsToPlainText()
    {
        $csrfProtector = new FakeCsrfProtector;
        $sut = $this->sut(["csrfProtector" => $csrfProtector]);
        $response = $sut->convertToPlainTextAction();
        $this->assertTrue($csrfProtector->hasChecked());
        Approvals::verifyHtml($response);
    }

    public function testImportsComments()
    {
        $csrfProtector = new FakeCsrfProtector;
        $sut = $this->sut(["csrfProtector" => $csrfProtector]);
        $response = $sut->importCommentsAction();
        $this->assertTrue($csrfProtector->hasChecked());
        Approvals::verifyHtml($response);
    }

    public function testImportsGbook()
    {
        $csrfProtector = new FakeCsrfProtector;
        $sut = $this->sut(["csrfProtector" => $csrfProtector]);
        $response = $sut->importGbookAction();
        $this->assertTrue($csrfProtector->hasChecked());
        Approvals::verifyHtml($response);
    }

    private function sut($options = [])
    {
        return new MainAdminController(
            "/",
            $this->conf($options["conf"] ?? []),
            $options["csrfProtector"] ?? new FakeCsrfProtector,
            $this->db(),
            $this->htmlCleaner(),
            $this->view()
        );
    }

    private function htmlCleaner()
    {
        return $this->createStub(HtmlCleaner::class);
    }

    private function db()
    {
        $db = $this->createStub(Db::class);
        $db->method("findAllTopics")->willReturn(["topic1"]);
        $db->method("findTopic")->willReturn([$this->comment()]);
        $db->method("findCommentsTopic")->willReturn([$this->comment()]);
        $db->method("findGbookTopic")->willReturn([$this->comment()]);
        return $db;
    }

    private function view()
    {
        return new View("./views/", XH_includeVar("./languages/en.php", "plugin_tx")["twocents"]);
    }

    private function conf($options = [])
    {
        $conf = XH_includeVar("./config/config.php", "plugin_cf")["twocents"];
        return $options + $conf;
    }

    private function comment()
    {
        return new Comment(
            "63fba86870945",
            "topic1",
            1677437048,
            "cmb",
            "cmb@example.com",
            "A nice comment",
            false
        );
    }
}
