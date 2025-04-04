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
use Plib\FakeRequest;
use Plib\Random;
use Plib\View;
use Twocents\Infra\FakeCaptcha;
use Twocents\Infra\FakeCsrfProtector;
use Twocents\Infra\FakeDb;
use Twocents\Infra\FakeHtmlCleaner;
use Twocents\Infra\FakeMailer;
use Twocents\Value\Comment;

class MainControllerTest extends TestCase
{
    public function testReportsInvalidTopicName(): void
    {
        $sut = $this->sut();
        $response = $sut(new FakeRequest(), "invalid!topicname", false);
        Approvals::verifyHtml($response->output());
    }

    public function testTogglesVisibility(): void
    {
        $csrfProtector = new FakeCsrfProtector;
        $db = new FakeDb;
        $db->insertComment($this->comment());
        $sut = $this->sut(["csrfProtector" => $csrfProtector, "db" => $db]);
        $request = new FakeRequest([
            "url" => "http://example.com/?Twocents&twocents_id=63fba86870945&twocents_action=toggle_visibility",
            "admin" => true,
            "post" => ["twocents_do" => ""],
        ]);
        $response = $sut($request, "test-topic", false);
        $comment = $db->findComment($this->comment()->topicname(), $this->comment()->id());
        $this->assertTrue($comment->hidden());
        $this->assertEquals("http://example.com/?Twocents", $response->location());
    }

    public function testRemovesComment(): void
    {
        $csrfProtector = new FakeCsrfProtector;
        $db = new FakeDb;
        $db->insertComment($this->comment());
        $sut = $this->sut(["csrfProtector" => $csrfProtector, "db" => $db]);
        $request = new FakeRequest([
            "url" => "http://example.com/?Twocents&twocents_id=63fba86870945&twocents_action=delete",
            "admin" => true,
            "post" => ["twocents_do" => ""]
        ]);
        $response = $sut($request, "test-topic", false);
        $this->assertTrue($csrfProtector->hasChecked());
        $this->assertNull($db->findComment($this->comment()->topicname(), $this->comment()->id()));
        $this->assertEquals("http://example.com/?Twocents", $response->location());
    }

    public function testRendersOverview(): void
    {
        $csrfProtector = new FakeCsrfProtector;
        $db = new FakeDb;
        $db->insertComment($this->comment());
        $sut = $this->sut(["csrfProtector" => $csrfProtector, "db" => $db]);
        $request = new FakeRequest([
            "url" => "http://example.com/?Twocents",
            "admin" => true,
        ]);
        $response = $sut($request, "test-topic", false);
        Approvals::verifyHtml($response->output());
    }

    public function testRendersOverviewWithPagination(): void
    {
        $csrfProtector = new FakeCsrfProtector;
        $db = new FakeDb;
        for ($i = 1677437048; $i < 1677437068; $i++) {
            $db->insertComment($this->comment((string) $i, $i));
        }
        $sut = $this->sut(["conf" => ["pagination_max" => "3"], "csrfProtector" => $csrfProtector, "db" => $db]);
        $request = new FakeRequest(["url" => "http://example.com/?Twocents"]);
        $response = $sut($request, "test-topic", false);
        Approvals::verifyHtml($response->output());
    }

    public function testRendersSingleComment(): void
    {
        $csrfProtector = new FakeCsrfProtector;
        $db = new FakeDb;
        $db->insertComment($this->comment("63fba86870945", 1677437048, true));
        $request = new FakeRequest([
            "url" => "http://example.com/?Twocents&twocents_action=show&twocents_id=63fba86870945",
        ]);
        $sut = $this->sut(["db" => $db]);
        $response = $sut($request, "test-topic", false);
        Approvals::verifyHtml($response->output());
    }

    public function testRendersCreateForm(): void
    {
        $csrfProtector = new FakeCsrfProtector;
        $db = new FakeDb;
        $db->insertComment($this->comment());
        $sut = $this->sut(["csrfProtector" => $csrfProtector, "db" => $db]);
        $request = new FakeRequest([
            "url" => "http://example.com/?Twocents&twocents_action=create",
            "admin" => true,
        ]);
        $response = $sut($request, "test-topic", false);
        Approvals::verifyHtml($response->output());
    }

    public function testRendersModeratedCreateForm(): void
    {
        $db = new FakeDb;
        $db->insertComment($this->comment());
        $sut = $this->sut(["conf" => ["comments_moderated" => "true"], "db" => $db]);
        $request = new FakeRequest([
            "url" => "http://example.com/?Twocents&twocents_action=create",
        ]);
        $response = $sut($request, "test-topic", false);
        Approvals::verifyHtml($response->output());
    }

    public function testReportsAuthorizationFailureToCreateComment(): void
    {
        $sut = $this->sut();
        $request = new FakeRequest([
            "url" => "http://example.com/?Twocents&twocents_action=create",
        ]);
        $response = $sut($request, "test-topic", true);
        Approvals::verifyHtml($response->output());
    }

    public function testRendersEditForm(): void
    {
        $csrfProtector = new FakeCsrfProtector;
        $db = new FakeDb;
        $db->insertComment($this->comment());
        $sut = $this->sut(["csrfProtector" => $csrfProtector, "db" => $db]);
        $request = new FakeRequest([
            "url" => "http://example.com/?Twocents&twocents_id=63fba86870945&twocents_action=edit",
            "admin" => true,
        ]);
        $response = $sut($request, "test-topic", false);
        Approvals::verifyHtml($response->output());
    }

    public function testReportsAuthorizationFailureToUpdateComment(): void
    {
        $sut = $this->sut();
        $request = new FakeRequest([
            "url" => "http://example.com/?Twocents&twocents_action=edit",
        ]);
        $response = $sut($request, "test-topic", false);
        Approvals::verifyHtml($response->output());
    }

    public function testReportsMissingCommentForUpdate(): void
    {
        $sut = $this->sut();
        $request = new FakeRequest([
            "url" => "http://example.com/?Twocents&twocents_action=edit",
            "admin" => true,
        ]);
        $response = $sut($request, "test-topic", false);
        Approvals::verifyHtml($response->output());
    }

    public function testAddsComment(): void
    {
        $csrfProtector = new FakeCsrfProtector;
        $sut = $this->sut(["csrfProtector" => $csrfProtector]);
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
        $response = $sut($request, "test-topic", false);
        $this->assertEquals(
            "http://example.com/?Twocents&twocents_action=show&twocents_id=G7RHKV5AQVAF110L31TU0D7P",
            $response->location()
        );
    }

    public function testNewCommentSendsNotificationEmail(): void
    {
        $mailer = new FakeMailer;
        $sut = $this->sut(["conf" => ["email_address" => "admin@example.com"], "mailer" => $mailer]);
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
        $sut($request, "test-topic", false);
        $this->assertTrue($mailer->sent);
        Approvals::verifyAsJson($mailer->output);
    }

    public function testCleansHtmlComment(): void
    {
        $csrfProtector = new FakeCsrfProtector;
        $db = new FakeDb();
        $sut = $this->sut(["conf" => ["comments_markup" => "HTML"], "csrfProtector" => $csrfProtector, "db" => $db]);
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
        $sut($request, "test-topic", false);
        $comment = $db->findComment("test-topic", "G7RHKV5AQVAF110L31TU0D7P");
        $this->assertEquals("<p>This is an image: .</p>", $comment->message());
    }

    public function testOnlyAdminCanAddCommentIfReadOnly(): void
    {
        $db = new FakeDb;
        $db->insertComment($this->comment());
        $sut = $this->sut(["csrfProtector" => null, "db" => $db]);
        $request = new FakeRequest([
            "url" => "http://example.com/?Twocents&twocents_action=create",
            "time" => 1677493797,
        ]);
        $response = $sut($request, "test-topic", true);
        Approvals::verifyHtml($response->output());
    }

    public function testReporsValidationErrorsWhenCreatingComment(): void
    {
        $csrfProtector = new FakeCsrfProtector;
        $sut = $this->sut(["csrfProtector" => $csrfProtector]);
        $request = new FakeRequest([
            "url" => "http://example.com/?Twocents&twocents_action=create",
            "admin" => true,
            "time" => 1677493797,
            "post" => ["twocents_do" => ""],
        ]);
        $response = $sut($request, "test-topic", false);
        Approvals::verifyHtml($response->output());
    }

    public function testReporsFailureToStoreWhenCreatingComment(): void
    {
        $csrfProtector = new FakeCsrfProtector;
        $db = new FakeDb(["insert" => false]);
        $sut = $this->sut(["csrfProtector" => $csrfProtector, "db" => $db]);
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
        $response = $sut($request, "test-topic", false);
        Approvals::verifyHtml($response->output());
    }

    public function testUpdatesComment(): void
    {
        $comment = $this->comment();
        $csrfProtector = new FakeCsrfProtector;
        $db = new FakeDb;
        $db->insertComment($comment);
        $sut = $this->sut(["csrfProtector" => $csrfProtector, "db" => $db]);
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
        $response = $sut($request, "test-topic", false);
        $this->assertEquals("http://example.com/?Twocents", $response->location());
    }

    public function testReportsAuthorizationFailureWhenUpdatingComment(): void
    {
        $sut = $this->sut();
        $request = new FakeRequest([
            "url" => "http://example.com/?Twocents&twocents_action=edit",
        ]);
        $response = $sut($request, "test-topic", false);
        Approvals::verifyHtml($response->output());
    }

    public function testReportsFailureToFindCommentWhenUpdating(): void
    {
        $db = new FakeDb(["insert" => false]);
        $sut = $this->sut(["db" => $db]);
        $request = new FakeRequest([
            "url" => "http://example.com/?Twocents&twocents_action=edit",
            "admin" => true,
        ]);
        $response = $sut($request, "test-topic", false);
        Approvals::verifyHtml($response->output());
    }

    public function testReporsValidationErrorsWhenUpdatingComment(): void
    {
        $csrfProtector = new FakeCsrfProtector;
        $db = new FakeDb;
        $db->insertComment($this->comment());
        $sut = $this->sut(["csrfProtector" => $csrfProtector, "db" => $db]);
        $request = new FakeRequest([
            "url" => "http://example.com/?Twocents&twocents_id=63fba86870945&twocents_action=edit",
            "admin" => true,
            "post" => ["twocents_do" => ""],
        ]);
        $response = $sut($request, "test-topic", false);
        Approvals::verifyHtml($response->output());
    }

    public function testReportsFailureToStoreWhenUpdatingComment(): void
    {
        $comment = $this->comment();
        $csrfProtector = new FakeCsrfProtector;
        $db = new FakeDb(["update" => false]);
        $db->insertComment($comment);
        $sut = $this->sut(["csrfProtector" => $csrfProtector, "db" => $db]);
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
        $response = $sut($request, "test-topic", false);
        Approvals::verifyHtml($response->output());
    }

    public function testReportsMissingAuthorizationToToggleVisibility(): void
    {
        $sut = $this->sut();
        $request = new FakeRequest([
            "url" => "http://example.com/?Twocents&twocents_action=toggle_visibility",
            "post" => ["twocents_do" => ""],
        ]);
        $response = $sut($request, "test-topic", false);
        Approvals::verifyHtml($response->output());
    }

    public function testReportsFailureToFindCommentWhenTogglingVisibility(): void
    {
        $sut = $this->sut();
        $request = new FakeRequest([
            "url" => "http://example.com/?Twocents&twocents_action=toggle_visibility",
            "admin" => true,
            "post" => ["twocents_do" => ""],
        ]);
        $response = $sut($request, "test-topic", false);
        Approvals::verifyHtml($response->output());
    }

    public function testReportsFailureToStoreWhenTogglingVisibility(): void
    {
        $db = new FakeDb(["update" => false]);
        $db->insertComment($this->comment());
        $sut = $this->sut(["db" => $db]);
        $request = new FakeRequest([
            "url" => "http://example.com/?Twocents&twocents_id=63fba86870945&twocents_action=toggle_visibility",
            "admin" => true,
            "post" => ["twocents_do" => ""],
        ]);
        $response = $sut($request, "test-topic", false);
        Approvals::verifyHtml($response->output());
    }

    public function testReportsMissingAuthorizationToDelete(): void
    {
        $sut = $this->sut();
        $request = new FakeRequest([
            "url" => "http://example.com/?Twocents&twocents_action=delete",
            "post" => ["twocents_do" => ""],
        ]);
        $response = $sut($request, "test-topic", false);
        Approvals::verifyHtml($response->output());
    }

    public function testReportsFailureToFindCommentWhenDeleting(): void
    {
        $sut = $this->sut();
        $request = new FakeRequest([
            "url" => "http://example.com/?Twocents&twocents_id=63fba86870945&twocents_action=delete",
            "admin" => true,
            "post" => ["twocents_do" => ""],
        ]);
        $response = $sut($request, "test-topic", false);
        Approvals::verifyHtml($response->output());
    }

    public function testReportsFailureToStoreWhenDeleting(): void
    {
        $db = new FakeDb(["delete" => false]);
        $db->insertComment($this->comment());
        $sut = $this->sut(["db" => $db]);
        $request = new FakeRequest([
            "url" => "http://example.com/?Twocents&twocents_id=63fba86870945&twocents_action=delete",
            "admin" => true,
            "post" => ["twocents_do" => ""],
        ]);
        $response = $sut($request, "test-topic", false);
        Approvals::verifyHtml($response->output());
    }

    private function sut($options = [])
    {
        return new MainController(
            "./plugins/twocents/",
            $this->conf($options["conf"] ?? []),
            $options["csrfProtector"] ?? new FakeCsrfProtector,
            $options["db"] ?? new FakeDb,
            new FakeHtmlCleaner("./plugins/twocents/"),
            $this->random(),
            new FakeCaptcha,
            $options["mailer"] ?? new FakeMailer,
            new View("./views/", $this->text())
        );
    }

    private function random()
    {
        $random = $this->createStub(Random::class);
        $random->method("bytes")->willReturn(hex2bin("81f71a7caad7d4f08415187be034f9"));
        return $random;
    }

    private function conf(array $options)
    {
        return $options + XH_includeVar("./config/config.php", "plugin_cf")["twocents"];
    }

    private function text()
    {
        return XH_includeVar("./languages/en.php", "plugin_tx")["twocents"];
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
