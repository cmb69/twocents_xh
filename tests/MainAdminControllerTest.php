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
use Plib\FakeRequest;
use Twocents\Infra\FakeCsrfProtector;
use Twocents\Infra\FakeDb;
use Twocents\Infra\FlashMessage;
use Twocents\Infra\HtmlCleaner;
use Twocents\Infra\View;
use Twocents\Value\Comment;

class MainAdminControllerTest extends TestCase
{
    public function testRendersOverview(): void
    {
        $sut = $this->sut();
        $response = $sut(new FakeRequest());
        $this->assertEquals("Twocents – Conversion", $response->title());
        Approvals::verifyHtml($response->output());
    }

    public function testRendersOverviewForHtml(): void
    {
        $sut = $this->sut(["conf" => ["comments_markup" => "HTML"]]);
        $response = $sut(new FakeRequest());
        $this->assertEquals("Twocents – Conversion", $response->title());
        Approvals::verifyHtml($response->output());
    }

    public function testRendersConvertToHtmlConfirmation(): void
    {
        $sut = $this->sut();
        $request = new FakeRequest([
            "url" => "http://example.com/?twocents&admin=plugin_main&twocents_action=convert_to_html",
        ]);
        $response = $sut($request);
        $this->assertEquals("Twocents – Conversion", $response->title());
        Approvals::verifyHtml($response->output());
    }

    public function testConvertsToHtml()
    {
        $csrfProtector = new FakeCsrfProtector;
        $db = new FakeDb;
        $db->insertComment($this->comment());
        $sut = $this->sut(["csrfProtector" => $csrfProtector, "db" => $db]);
        $request = new FakeRequest([
            "url" => "http://example.com/?twocents&admin=plugin_main&twocents_action=convert_to_html",
            "post" => ["twocents_do" => ""],
        ]);
        $response = $sut($request);
        $this->assertTrue($csrfProtector->hasChecked());
        $this->assertEquals($this->comment()->topicname(), $db->lastTopicStored);
        $this->assertEquals("http://example.com/?twocents&admin=plugin_main", $response->location());
    }

    public function testRendersConvertToPlainTextConfirmation(): void
    {
        $sut = $this->sut();
        $request = new FakeRequest([
            "url" => "http://example.com/?twocents&admin=plugin_main&twocents_action=convert_to_plain_text",
        ]);
        $response = $sut($request);
        $this->assertEquals("Twocents – Conversion", $response->title());
        Approvals::verifyHtml($response->output());
    }

    public function testConvertsToPlainText()
    {
        $csrfProtector = new FakeCsrfProtector;
        $db = new FakeDb;
        $db->insertComment($this->comment());
        $sut = $this->sut(["csrfProtector" => $csrfProtector, "db" => $db]);
        $request = new FakeRequest([
            "url" => "http://example.com/?twocents&admin=plugin_main&twocents_action=convert_to_plain_text",
            "post" => ["twocents_do" => ""],
        ]);
        $response = $sut($request);
        $this->assertTrue($csrfProtector->hasChecked());
        $this->assertEquals($this->comment()->topicname(), $db->lastTopicStored);
        $this->assertEquals("http://example.com/?twocents&admin=plugin_main", $response->location());
    }

    public function testRendersImportCommentsConfirmation(): void
    {
        $sut = $this->sut();
        $request = new FakeRequest([
            "url" => "http://example.com/?twocents&admin=plugin_main&twocents_action=import_comments",
        ]);
        $response = $sut($request);
        $this->assertEquals("Twocents – Conversion", $response->title());
        Approvals::verifyHtml($response->output());
    }

    public function testImportsComments()
    {
        $csrfProtector = new FakeCsrfProtector;
        $db = new FakeDb;
        $db->insertComment($this->comment());
        $sut = $this->sut(["csrfProtector" => $csrfProtector, "db" => $db]);
        $request = new FakeRequest([
            "url" => "http://example.com/?twocents&admin=plugin_main&twocents_action=import_comments",
            "post" => ["twocents_do" => ""]
        ]);
        $response = $sut($request);
        $this->assertEquals($this->comment()->topicname(), $db->lastTopicStored);
        $this->assertTrue($csrfProtector->hasChecked());
        $this->assertEquals("http://example.com/?twocents&admin=plugin_main", $response->location());
    }

    public function testRendersImportGbookConfirmation(): void
    {
        $sut = $this->sut();
        $request = new FakeRequest([
            "url" => "http://example.com/?twocents&admin=plugin_main&twocents_action=import_gbook",
        ]);
        $response = $sut($request);
        $this->assertEquals("Twocents – Conversion", $response->title());
        Approvals::verifyHtml($response->output());
    }

    public function testImportsGbook()
    {
        $csrfProtector = new FakeCsrfProtector;
        $db = new FakeDb;
        $db->insertComment($this->comment());
        $sut = $this->sut(["csrfProtector" => $csrfProtector, "db" => $db]);
        $request = new FakeRequest([
            "url" => "http://example.com/?twocents&admin=plugin_main&twocents_action=import_gbook",
            "post" => ["twocents_do" => ""],
        ]);
        $response = $sut($request);
        $this->assertTrue($csrfProtector->hasChecked());
        $this->assertEquals($this->comment()->topicname(), $db->lastTopicStored);
        $this->assertEquals("http://example.com/?twocents&admin=plugin_main", $response->location());
    }

    private function sut($options = [])
    {
        return new MainAdminController(
            $this->conf($options["conf"] ?? []),
            $options["csrfProtector"] ?? new FakeCsrfProtector,
            $options["db"] ?? new FakeDb,
            $this->htmlCleaner(),
            $this->flashMessage(),
            $this->view()
        );
    }

    private function htmlCleaner()
    {
        return $this->createStub(HtmlCleaner::class);
    }

    private function flashMessage()
    {
        $flashMessage = $this->createStub(FlashMessage::class);
        $flashMessage->method("pop")->willReturn("");
        return $flashMessage;
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
