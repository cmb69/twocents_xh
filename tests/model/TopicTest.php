<?php

namespace Twocents\Model;

use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;
use Plib\DocumentStore;

/** @small */
class TopicTest extends TestCase
{
    /** @var DocumentStore */
    private $store;

    public function setUp(): void
    {
        vfsStream::setup("root");
        $this->store = new DocumentStore(vfsStream::url("root/"));
    }

    public function testGeneratesMissingIds(): void
    {
        $topic = Topic::update("test-topic", $this->store);
        $topic->addComment($this->comment(""));
        $this->store->commit();
        $topic = Topic::retrieve("test-topic", $this->store, fn () => "12345");
        $this->assertNotNull($topic->comment("12345"));
    }

    public function testGbookReading(): void
    {
        $gbook = "Joachim Barthel;jbarthel@qualifire.de;www.qualifire.de/cmsimple;11:03:08;26.02.2006"
            . ";Das Gästebuch ist online/The Guestbook is online;Das Gästebuch ist online/The guestbook is online."
            . "Neue Beiträge sind herzlich willkommen/New entries are welcome!;127.0.0.1";
        $message = "<p><strong>Das Gästebuch ist online/The Guestbook is online</strong></p>"
            . "<p>Das Gästebuch ist online/The guestbook is online."
            . "Neue Beiträge sind herzlich willkommen/New entries are welcome!</p>";
        file_put_contents(vfsStream::url("root/guestbook.txt"), $gbook);
        $topic = Topic::retrieve("guestbook", $this->store, fn () => "12345");
        $this->assertCount(1, $topic->comments());
        $comment = $topic->comments()[0];
        $this->assertSame("12345", $comment->id());
        $this->assertSame(strtotime("2006-02-26T11:03:08+00:00"), $comment->time());
        $this->assertSame("Joachim Barthel", $comment->user());
        $this->assertSame("jbarthel@qualifire.de", $comment->email());
        $this->assertSame($message, $comment->message());
        $this->assertFalse($comment->hidden());
    }

    public function testCommentsReading(): void
    {
        $comments = "Comments 4.11-,+;-admin-,+;-open-,+;--,+;--,+;--,+;--,+;-\n"
            . "-,+;-Christoph M. Becker-,+;-cmbecker69@gmx.de-,+;-https://3-magi.net-,+;-::1"
            . "-,+;-1677419395-,+;-./content/comments/upload/pic0~01.jpg-,+;-<p>This is my comment.</p>";
        file_put_contents(vfsStream::url("root/comments.txt"), $comments);
        $topic = Topic::retrieve("comments", $this->store, fn () => "12345");
        $this->assertCount(1, $topic->comments());
        $comment = $topic->comments()[0];
        $this->assertSame("12345", $comment->id());
        $this->assertSame(strtotime("2023-02-26T13:49:55+00:00"), $comment->time());
        $this->assertSame("Christoph M. Becker", $comment->user());
        $this->assertSame("cmbecker69@gmx.de", $comment->email());
        $this->assertSame("<p>This is my comment.</p>", $comment->message());
        $this->assertFalse($comment->hidden());
    
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
