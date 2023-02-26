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
use Twocents\Infra\FakeDb;
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
        $db = new FakeDb;
        $db->insertComment($this->comment());
        $sut = $this->sut(["csrfProtector" => $csrfProtector, "db" => $db]);
        $response = $sut->convertToHtmlAction();
        $this->assertTrue($csrfProtector->hasChecked());
        $this->assertEquals($this->comment()->topicname(), $db->lastTopicStored);
        Approvals::verifyHtml($response);
    }

    public function testConvertsToPlainText()
    {
        $csrfProtector = new FakeCsrfProtector;
        $db = new FakeDb;
        $db->insertComment($this->comment());
        $sut = $this->sut(["csrfProtector" => $csrfProtector, "db" => $db]);
        $response = $sut->convertToPlainTextAction();
        $this->assertTrue($csrfProtector->hasChecked());
        $this->assertEquals($this->comment()->topicname(), $db->lastTopicStored);
        Approvals::verifyHtml($response);
    }

    public function testImportsComments()
    {
        $csrfProtector = new FakeCsrfProtector;
        $db = new FakeDb;
        $db->insertComment($this->comment());
        $sut = $this->sut(["csrfProtector" => $csrfProtector, "db" => $db]);
        $response = $sut->importCommentsAction();
        $this->assertEquals($this->comment()->topicname(), $db->lastTopicStored);
        $this->assertTrue($csrfProtector->hasChecked());
        Approvals::verifyHtml($response);
    }

    public function testImportsGbook()
    {
        $csrfProtector = new FakeCsrfProtector;
        $db = new FakeDb;
        $db->insertComment($this->comment());
        $sut = $this->sut(["csrfProtector" => $csrfProtector, "db" => $db]);
        $response = $sut->importGbookAction();
        $this->assertTrue($csrfProtector->hasChecked());
        $this->assertEquals($this->comment()->topicname(), $db->lastTopicStored);
        Approvals::verifyHtml($response);
    }

    private function sut($options = [])
    {
        return new MainAdminController(
            "/",
            $this->conf($options["conf"] ?? []),
            $options["csrfProtector"] ?? new FakeCsrfProtector,
            $options["db"] ?? new FakeDb,
            $this->htmlCleaner(),
            $this->view()
        );
    }

    private function htmlCleaner()
    {
        return $this->createStub(HtmlCleaner::class);
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
