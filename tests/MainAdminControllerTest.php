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
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Plib\CsrfProtector;
use Plib\DocumentStore;
use Plib\FakeRequest;
use Plib\View;
use Twocents\Infra\FlashMessage;
use Twocents\Model\Comment;
use Twocents\Model\Topic;

class MainAdminControllerTest extends TestCase
{
    /** @var array<string,string> */
    private $conf;

    /** @var CsrfProtector&Stub */
    private $csrfProtector;

    /** @var DocumentStore */
    private $store;

    /** @var FlashMessage&Stub */
    private $flashMessage;

    /** @var View */
    private $view;

    public function setUp(): void
    {
        vfsStream::setup("root");
        $this->conf = XH_includeVar("./config/config.php", "plugin_cf")["twocents"];
        $this->csrfProtector = $this->createStub(CsrfProtector::class);
        $this->csrfProtector->method("token")->willReturn("e3c1b42a6098b48a39f9f54ddb3388f7");
        $this->store = new DocumentStore(vfsStream::url("root/"));
        $this->flashMessage = $this->createStub(FlashMessage::class);
        $this->flashMessage->method("pop")->willReturn("");
        $this->view = new View("./views/", XH_includeVar("./languages/en.php", "plugin_tx")["twocents"]);
    }

    private function sut()
    {
        return new MainAdminController(
            $this->conf,
            $this->csrfProtector,
            $this->store,
            $this->flashMessage,
            $this->view
        );
    }

    public function testRendersOverview(): void
    {
        $response = $this->sut()(new FakeRequest());
        $this->assertEquals("Twocents – Conversion", $response->title());
        Approvals::verifyHtml($response->output());
    }

    public function testRendersOverviewForHtml(): void
    {
        $this->conf["comments_markup"] = "HTML";
        $response = $this->sut()(new FakeRequest());
        $this->assertEquals("Twocents – Conversion", $response->title());
        Approvals::verifyHtml($response->output());
    }

    public function testRendersConvertToHtmlConfirmation(): void
    {
        $request = new FakeRequest([
            "url" => "http://example.com/?twocents&admin=plugin_main&twocents_action=convert_to_html",
        ]);
        $response = $this->sut()($request);
        $this->assertEquals("Twocents – Conversion", $response->title());
        Approvals::verifyHtml($response->output());
    }

    public function testConvertToHtmlIsCsrfProtected(): void
    {
        $this->csrfProtector->method("check")->willReturn(false);
        $request = new FakeRequest([
            "url" => "http://example.com/?twocents&admin=plugin_main&twocents_action=convert_to_html",
            "post" => ["twocents_do" => ""],
        ]);
        $response = $this->sut()($request);
        $this->assertStringContainsString("You are not authorized for this operation!", $response->output());
    }

    public function testConvertsToHtml()
    {
        $this->csrfProtector->method("check")->willReturn(true);
        $this->store($this->comment());
        $request = new FakeRequest([
            "url" => "http://example.com/?twocents&admin=plugin_main&twocents_action=convert_to_html",
            "post" => ["twocents_do" => ""],
        ]);
        $response = $this->sut()($request);
        $this->assertSame(
            "<p>A nice comment</p>",
            Topic::retrieve("topic1", $this->store)->comment("63fba86870945")->message()
        );
        $this->assertEquals("http://example.com/?twocents&admin=plugin_main", $response->location());
    }

    public function testRendersConvertToPlainTextConfirmation(): void
    {
        $request = new FakeRequest([
            "url" => "http://example.com/?twocents&admin=plugin_main&twocents_action=convert_to_plain_text",
        ]);
        $response = $this->sut()($request);
        $this->assertEquals("Twocents – Conversion", $response->title());
        Approvals::verifyHtml($response->output());
    }

    public function testConvertToPlainTextIsCsrfProtected(): void
    {
        $this->csrfProtector->method("check")->willReturn(false);
        $request = new FakeRequest([
            "url" => "http://example.com/?twocents&admin=plugin_main&twocents_action=convert_to_plain_text",
            "post" => ["twocents_do" => ""],
        ]);
        $response = $this->sut()($request);
        $this->assertStringContainsString("You are not authorized for this operation!", $response->output());
    }

    public function testConvertsToPlainText()
    {
        $this->csrfProtector->method("check")->willReturn(true);
        $this->store($this->comment());
        $request = new FakeRequest([
            "url" => "http://example.com/?twocents&admin=plugin_main&twocents_action=convert_to_plain_text",
            "post" => ["twocents_do" => ""],
        ]);
        $response = $this->sut()($request);
        $this->assertSame(
            "A nice comment",
            Topic::retrieve("topic1", $this->store)->comment("63fba86870945")->message()
        );
        $this->assertEquals("http://example.com/?twocents&admin=plugin_main", $response->location());
    }

    private function store(Comment $comment): void
    {
        $topic = Topic::update("topic1", $this->store);
        $topic->addComment($comment);
        $this->store->commit();
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
