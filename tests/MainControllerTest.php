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
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Plib\CsrfProtector;
use Plib\DocumentStore;
use Plib\FakeRequest;
use Plib\Random;
use Plib\View;
use Twocents\Infra\FakeCaptcha;
use Twocents\Infra\FakeMailer;
use Twocents\Infra\HtmlCleaner;
use Twocents\Model\Comment;
use Twocents\Model\Topic;

class MainControllerTest extends TestCase
{
    /** @var array<string,string> */
    private $conf;

    /** @var CsrfProtector&Stub */
    private $csrfProtector;

    /** @var DocumentStore */
    private $store;

    /** @var HtmlCleaner */
    private $htmlCleaner;

    /** @var Random&Stub */
    private $random;

    /** @var FakeCaptcha */
    private $captcha;

    /** @var FakeMailer */
    private $mailer;

    /** @var View */
    private $view;

    public function setUp(): void
    {
        vfsStream::setup("root");
        $this->conf = XH_includeVar("./config/config.php", "plugin_cf")["twocents"];
        $this->csrfProtector = $this->createStub(CsrfProtector::class);
        $this->csrfProtector->method("token")->willReturn("e3c1b42a6098b48a39f9f54ddb3388f7");
        $this->store = new DocumentStore(vfsStream::url("root/"));
        $this->htmlCleaner = new HtmlCleaner("./");
        $this->random = $this->createStub(Random::class);
        $this->random->method("bytes")->willReturn(hex2bin("81f71a7caad7d4f08415187be034f9"));
        $this->captcha = new FakeCaptcha();
        $this->mailer = new FakeMailer();
        $this->view = new View("./views/", XH_includeVar("./languages/en.php", "plugin_tx")["twocents"]);
    }

    public function testReportsInvalidTopicName(): void
    {
        $sut = $this->sut();
        $response = $sut(new FakeRequest(), "invalid!topicname", false);
        Approvals::verifyHtml($response->output());
    }

    private function sut()
    {
        return new MainController(
            "./plugins/twocents/",
            $this->conf,
            $this->csrfProtector,
            $this->store,
            $this->htmlCleaner,
            $this->random,
            $this->captcha,
            $this->mailer,
            $this->view
        );
    }

    public function testTogglesVisibility(): void
    {
        $this->csrfProtector->method("check")->willReturn(true);
        $this->store($this->comment());
        $request = new FakeRequest([
            "url" => "http://example.com/?Twocents&twocents_id=63fba86870945&twocents_action=toggle_visibility",
            "admin" => true,
            "post" => ["twocents_do" => ""],
        ]);
        $response = $this->sut()($request, "test-topic", false);
        $comment = Topic::retrieve("test-topic", $this->store)->comment($this->comment()->id());
        $this->assertTrue($comment->hidden());
        $this->assertEquals("http://example.com/?Twocents", $response->location());
    }

    public function testRemovesComment(): void
    {
        $this->csrfProtector->method("check")->willReturn(true);
        $this->store($this->comment());
        $request = new FakeRequest([
            "url" => "http://example.com/?Twocents&twocents_id=63fba86870945&twocents_action=delete",
            "admin" => true,
            "post" => ["twocents_do" => ""]
        ]);
        $response = $this->sut()($request, "test-topic", false);
        $this->assertNull(Topic::retrieve("test-topic", $this->store)->comment($this->comment()->id()));
        $this->assertEquals("http://example.com/?Twocents", $response->location());
    }

    public function testRendersOverview(): void
    {
        $this->store($this->comment());
        $request = new FakeRequest([
            "url" => "http://example.com/?Twocents",
            "admin" => true,
        ]);
        $response =  $this->sut()($request, "test-topic", false);
        Approvals::verifyHtml($response->output());
    }

    public function testRendersOverviewWithPagination(): void
    {
        $topic = Topic::update("test-topic", $this->store);
        for ($i = 1677437048; $i < 1677437068; $i++) {
            $topic->addComment($this->comment((string) $i, $i));
        }
        $this->store->commit();
        $this->conf["pagination_max"] = "3";
        $request = new FakeRequest(["url" => "http://example.com/?Twocents"]);
        $response = $this->sut()($request, "test-topic", false);
        Approvals::verifyHtml($response->output());
    }

    public function testRendersSingleComment(): void
    {
        $this->store($this->comment("63fba86870945", 1677437048, true));
        $request = new FakeRequest([
            "url" => "http://example.com/?Twocents&twocents_action=show&twocents_id=63fba86870945",
        ]);
        $response = $this->sut()($request, "test-topic", false);
        Approvals::verifyHtml($response->output());
    }

    public function testRendersCreateForm(): void
    {
        $request = new FakeRequest([
            "url" => "http://example.com/?Twocents&twocents_action=create",
            "admin" => true,
        ]);
        $response = $this->sut()($request, "test-topic", false);
        Approvals::verifyHtml($response->output());
    }

    public function testRendersModeratedCreateForm(): void
    {
        $this->conf["comments_moderated"] = "true";
        $request = new FakeRequest([
            "url" => "http://example.com/?Twocents&twocents_action=create",
        ]);
        $response = $this->sut()($request, "test-topic", false);
        Approvals::verifyHtml($response->output());
    }

    public function testReportsAuthorizationFailureToCreateComment(): void
    {
        $request = new FakeRequest([
            "url" => "http://example.com/?Twocents&twocents_action=create",
        ]);
        $response = $this->sut()($request, "test-topic", true);
        Approvals::verifyHtml($response->output());
    }

    public function testRendersEditForm(): void
    {
        $this->store($this->comment());
        $request = new FakeRequest([
            "url" => "http://example.com/?Twocents&twocents_id=63fba86870945&twocents_action=edit",
            "admin" => true,
        ]);
        $response = $this->sut()($request, "test-topic", false);
        Approvals::verifyHtml($response->output());
    }

    public function testReportsAuthorizationFailureToUpdateComment(): void
    {
        $request = new FakeRequest([
            "url" => "http://example.com/?Twocents&twocents_action=edit",
        ]);
        $response = $this->sut()($request, "test-topic", false);
        Approvals::verifyHtml($response->output());
    }

    public function testReportsMissingCommentForUpdate(): void
    {
        $request = new FakeRequest([
            "url" => "http://example.com/?Twocents&twocents_action=edit",
            "admin" => true,
        ]);
        $response = $this->sut()($request, "test-topic", false);
        Approvals::verifyHtml($response->output());
    }

    public function testAddsComment(): void
    {
        $request = new FakeRequest([
            "url" => "http://example.com/?Twocents&twocents_action=create",
            "admin" => true,
            "time" => 1677493797,
            "post" => [
                "twocents_user" => "cmb",
                "twocents_email" => "cmb69@gmx.de",
                "twocents_message" => "I fixed that typo",
                "twocents_do" => "",
            ],
        ]);
        $response = $this->sut()($request, "test-topic", false);
        $this->assertEquals(
            "http://example.com/?Twocents&twocents_action=show&twocents_id=G7RHKV5AQVAF110L31TU0D7P",
            $response->location()
        );
    }

    public function testNewCommentSendsNotificationEmail(): void
    {
        $this->conf["email_address"] = "admin@example.com";
        $request = new FakeRequest([
            "url" => "http://example.com/?Twocents&twocents_action=create",
            "admin" => false,
            "time" => 1677493797,
            "post" => [
                "twocents_user" => "cmb",
                "twocents_email" => "cmb69@gmx.de",
                "twocents_message" => "I fixed that typo",
                "twocents_do" => "",
            ],
        ]);
        $this->sut()($request, "test-topic", false);
        $this->assertTrue($this->mailer->sent);
        Approvals::verifyAsJson($this->mailer->output);
    }

    public function testCleansHtmlComment(): void
    {
        $this->conf["comments_markup"] = "HTML";
        $request = new FakeRequest([
            "url" => "http://example.com/?Twocents&twocents_action=create",
            "admin" => false,
            "time" => 1677493797,
            "post" => [
                "twocents_user" => "cmb",
                "twocents_email" => "cmb69@gmx.de",
                "twocents_message" => "<p>This is an image:&nbsp;<img src=\"irrelevant\">.</p>",
                "twocents_do" => "",
            ],
        ]);
        $this->sut()($request, "test-topic", false);
        $comment = Topic::retrieve("test-topic", $this->store)->comment("G7RHKV5AQVAF110L31TU0D7P");
        $this->assertStringMatchesFormat("<p>%wThis is an image: .%w</p>", $comment->message());
    }

    public function testOnlyAdminCanAddCommentIfReadOnly(): void
    {
        $request = new FakeRequest([
            "url" => "http://example.com/?Twocents&twocents_action=create",
            "time" => 1677493797,
        ]);
        $response = $this->sut()($request, "test-topic", true);
        Approvals::verifyHtml($response->output());
    }

    public function testReporsValidationErrorsWhenCreatingComment(): void
    {
        $request = new FakeRequest([
            "url" => "http://example.com/?Twocents&twocents_action=create",
            "admin" => true,
            "time" => 1677493797,
            "post" => ["twocents_do" => ""],
        ]);
        $response = $this->sut()($request, "test-topic", false);
        Approvals::verifyHtml($response->output());
    }

    public function testReporsFailureToStoreWhenCreatingComment(): void
    {
        vfsStream::setQuota(0);
        $request = new FakeRequest([
            "url" => "http://example.com/?Twocents&twocents_action=create",
            "admin" => true,
            "time" => 1677493797,
            "post" => [
                "twocents_user" => "cmb",
                "twocents_email" => "cmb69@gmx.de",
                "twocents_message" => "I fixed that typo",
                "twocents_do" => "",
            ],
        ]);
        $response = $this->sut()($request, "test-topic", false);
        Approvals::verifyHtml($response->output());
    }

    public function testUpdatesComment(): void
    {
        $this->csrfProtector->method("check")->willReturn(true);
        $this->store($this->comment());
        $request = new FakeRequest([
            "url" => "http://example.com/?Twocents&twocents_id=63fba86870945&twocents_action=edit",
            "admin" => true,
            "post" => [
                "twocents_user" => "cmb",
                "twocents_email" => "cmb69@gmx.de",
                "twocents_message" => "I fixed that typo",
                "twocents_do" => "",
            ],
        ]);
        $response = $this->sut()($request, "test-topic", false);
        $this->assertEquals("http://example.com/?Twocents", $response->location());
    }

    public function testReportsAuthorizationFailureWhenUpdatingComment(): void
    {
        $request = new FakeRequest([
            "url" => "http://example.com/?Twocents&twocents_action=edit",
        ]);
        $response = $this->sut()($request, "test-topic", false);
        Approvals::verifyHtml($response->output());
    }

    public function testReportsFailureToFindCommentWhenUpdating(): void
    {
        $request = new FakeRequest([
            "url" => "http://example.com/?Twocents&twocents_action=edit",
            "admin" => true,
        ]);
        $response = $this->sut()($request, "test-topic", false);
        Approvals::verifyHtml($response->output());
    }

    public function testReporsValidationErrorsWhenUpdatingComment(): void
    {
        $this->csrfProtector->method("check")->willReturn(true);
        $this->store($this->comment());
        $request = new FakeRequest([
            "url" => "http://example.com/?Twocents&twocents_id=63fba86870945&twocents_action=edit",
            "admin" => true,
            "post" => ["twocents_do" => ""],
        ]);
        $response = $this->sut()($request, "test-topic", false);
        Approvals::verifyHtml($response->output());
    }

    public function testReportsFailureToStoreWhenUpdatingComment(): void
    {
        $this->csrfProtector->method("check")->willReturn(true);
        $this->store($this->comment());
        vfsStream::setQuota(0);
        $request = new FakeRequest([
            "url" => "http://example.com/?Twocents&twocents_id=63fba86870945&twocents_action=edit",
            "admin" => true,
            "post" => [
                "twocents_user" => "cmb",
                "twocents_email" => "cmb69@gmx.de",
                "twocents_message" => "I fixed that typo",
                "twocents_do" => "",
            ],
        ]);
        $response = $this->sut()($request, "test-topic", false);
        Approvals::verifyHtml($response->output());
    }

    public function testReportsMissingAuthorizationToToggleVisibility(): void
    {
        $request = new FakeRequest([
            "url" => "http://example.com/?Twocents&twocents_action=toggle_visibility",
            "post" => ["twocents_do" => ""],
        ]);
        $response = $this->sut()($request, "test-topic", false);
        Approvals::verifyHtml($response->output());
    }

    public function testReportsFailureToFindCommentWhenTogglingVisibility(): void
    {
        $this->csrfProtector->method("check")->willReturn(true);
        $request = new FakeRequest([
            "url" => "http://example.com/?Twocents&twocents_action=toggle_visibility",
            "admin" => true,
            "post" => ["twocents_do" => ""],
        ]);
        $response = $this->sut()($request, "test-topic", false);
        Approvals::verifyHtml($response->output());
    }

    public function testReportsFailureToStoreWhenTogglingVisibility(): void
    {
        $this->csrfProtector->method("check")->willReturn(true);
        $this->store($this->comment());
        vfsStream::setQuota(0);
        $request = new FakeRequest([
            "url" => "http://example.com/?Twocents&twocents_id=63fba86870945&twocents_action=toggle_visibility",
            "admin" => true,
            "post" => ["twocents_do" => ""],
        ]);
        $response = $this->sut()($request, "test-topic", false);
        Approvals::verifyHtml($response->output());
    }

    public function testReportsMissingAuthorizationToDelete(): void
    {
        $request = new FakeRequest([
            "url" => "http://example.com/?Twocents&twocents_action=delete",
            "post" => ["twocents_do" => ""],
        ]);
        $response = $this->sut()($request, "test-topic", false);
        Approvals::verifyHtml($response->output());
    }

    public function testReportsFailureToFindCommentWhenDeleting(): void
    {
        $this->csrfProtector->method("check")->willReturn(true);
        $request = new FakeRequest([
            "url" => "http://example.com/?Twocents&twocents_id=63fba86870945&twocents_action=delete",
            "admin" => true,
            "post" => ["twocents_do" => ""],
        ]);
        $response = $this->sut()($request, "test-topic", false);
        Approvals::verifyHtml($response->output());
    }

    public function testReportsFailureToStoreWhenDeleting(): void
    {
        $this->csrfProtector->method("check")->willReturn(true);
        $this->store($this->comment("1.4-dev"));
        $this->store($this->comment());
        vfsStream::setQuota(0);
        $request = new FakeRequest([
            "url" => "http://example.com/?Twocents&twocents_id=63fba86870945&twocents_action=delete",
            "admin" => true,
            "post" => ["twocents_do" => ""],
        ]);
        $response = $this->sut()($request, "test-topic", false);
        Approvals::verifyHtml($response->output());
    }

    private function store(Comment $comment): void
    {
        $topic = Topic::update("test-topic", $this->store);
        $topic->addComment($comment);
        $this->store->commit();
    }

    private function comment(string $id = "63fba86870945", int $time = 1677437048, bool $hidden = false)
    {
        return new Comment(
            $id,
            "test-topic",
            $time,
            "cmb",
            "cmb@example.com",
            "A nice comment",
            $hidden
        );
    }
}
