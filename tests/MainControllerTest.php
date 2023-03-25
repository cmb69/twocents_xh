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
use Twocents\Infra\FakeCaptcha;
use Twocents\Infra\FakeCsrfProtector;
use Twocents\Infra\FakeDb;
use Twocents\Infra\FakeMailer;
use Twocents\Infra\FakeRequest;
use Twocents\Infra\HtmlCleaner;
use Twocents\Infra\Random;
use Twocents\Infra\View;
use Twocents\Value\Comment;

class MainControllerTest extends TestCase
{
    public function testTogglesVisibility(): void
    {
        $csrfProtector = new FakeCsrfProtector;
        $db = new FakeDb;
        $db->insertComment($this->comment());
        $sut = $this->sut(["csrfProtector" => $csrfProtector, "db" => $db]);
        $request = new FakeRequest([
            "query" => "Twocents&twocents_id=63fba86870945&twocents_action=toggle_visibility",
            "adm" => true,
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
            "query" => "Twocents&twocents_id=63fba86870945&twocents_action=delete",
            "adm" => true,
            "post" => ["twocents_do" => ""],
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
        $request = new FakeRequest(["query" => "Twocents", "adm" => true]);
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
        $request = new FakeRequest(["query" => "Twocents"]);
        $response = $sut($request, "test-topic", false);
        Approvals::verifyHtml($response->output());
    }

    public function testRendersEditForm(): void
    {
        $csrfProtector = new FakeCsrfProtector;
        $db = new FakeDb;
        $db->insertComment($this->comment());
        $sut = $this->sut(["csrfProtector" => $csrfProtector, "db" => $db]);
        $request = new FakeRequest(["query" => "Twocents&twocents_id=63fba86870945&twocents_action=edit"]);
        $response = $sut($request, "test-topic", false);
        Approvals::verifyHtml($response->output());
    }

    public function testAddsComment(): void
    {
        $csrfProtector = new FakeCsrfProtector;
        $sut = $this->sut(["csrfProtector" => $csrfProtector]);
        $request = new FakeRequest([
            "query" => "Twocents&twocents_action=create",
            "time" => 1677493797,
            "adm" => true,
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

    public function testNewCommentSendsNotificationEmail(): void
    {
        $mailer = new FakeMailer;
        $sut = $this->sut(["conf" => ["email_address" => "admin@example.com"], "mailer" => $mailer]);
        $request = new FakeRequest([
            "query" => "Twocents&twocents_action=create",
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

    public function testOnlyAdminCanAddCommentIfReadOnly(): void
    {
        $db = new FakeDb;
        $db->insertComment($this->comment());
        $sut = $this->sut(["csrfProtector" => null, "db" => $db]);
        $request = new FakeRequest([
            "query" => "Twocents&twocents_action=create",
            "time" => 1677493797,
            "post" => [
                "twocents_user" => "cmb",
                "twocents_email" => "cmb69@gmx.de",
                "twocents_message" => "I fixed that typo",
                "twocents_do" => "",
            ],
        ]);
        $response = $sut($request, "test-topic", true);
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
            "query" => "Twocents&twocents_id=63fba86870945&twocents_action=edit",
            "adm" => true,
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

    private function sut($options = [])
    {
        return new MainController(
            "./plugins/twocents/",
            $this->conf($options["conf"] ?? []),
            $options["csrfProtector"] ?? new FakeCsrfProtector,
            $options["db"] ?? new FakeDb,
            $this->createStub(HtmlCleaner::class),
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

    private function comment(string $id = "63fba86870945", int $time = 1677437048)
    {
        return new Comment(
            $id,
            "test-topic",
            $time,
            "cmb",
            "cmb@example.com",
            "A nice comment",
            false
        );
    }
}
