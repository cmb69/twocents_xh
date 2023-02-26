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

namespace Twocents\Infra;

use Error;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;
use Twocents\Infra\Db;
use Twocents\Value\Comment;

class DbTest extends TestCase
{
    /** @var Db */
    private $sut;

    public function setUp(): void
    {
        vfsStream::setup("root");
        mkdir(vfsStream::url("root/twocents/"));
        $this->sut = new Db(vfsStream::url("root/twocents/"));
    }

    public function testFindsAllTopics(): void
    {
        foreach (["foo", "bar", "baz"] as $name) {
            touch(vfsStream::url("root/twocents/$name.csv"));
        }
        $topics = $this->sut->findTopics();
        $this->assertEquals(["foo", "bar", "baz"], $topics);
    }

    public function testCreatesFolderIfItDoesNotExist(): void
    {
        vfsStream::setup("root");
        $sut = new DB(vfsStream::url("root/twocents/"));
        $foldername = $sut->getFolderName();
        $this->assertEquals(vfsStream::url("root/twocents/"), $foldername);
        $this->assertTrue(is_dir(vfsStream::url("root/twocents/")));
    }

    public function testFindsCommentsOfTopic(): void
    {
        $this->sut->insertComment($this->comment());
        $this->sut->insertComment($this->otherComment());
        $comments = $this->sut->findCommentsOfTopic($this->comment()->topicname());
        $this->assertEquals([$this->comment(), $this->otherComment()], $comments);
    }

    public function testFindsVisibleCommentsOfTopic(): void
    {
        $this->sut->insertComment($this->comment());
        $this->sut->insertComment($this->otherComment());
        $comments = $this->sut->findCommentsOfTopic($this->comment()->topicname(), true);
        $this->assertEquals([$this->comment()], $comments);
    }

    public function testStoresTopic(): void
    {
        $this->sut->storeTopic(
            $this->comment()->topicname(),
            [$this->comment(), $this->otherComment()]
        );
        $comments = $this->sut->findCommentsOfTopic($this->comment()->topicname());
        $this->assertEquals([$this->comment(), $this->otherComment()], $comments);
    }

    public function testThrowsWhenStoringTopicWithMismatchingComment(): void
    {
        $this->expectException(Error::class);
        $this->expectExceptionMessage("topic mismatch");
        $this->sut->storeTopic("wrong_topic", [$this->comment()]);
    }

    public function testInsertsComment(): void
    {
        $comment = $this->comment();
        $this->sut->insertComment($comment);
        $found = $this->sut->findComment($comment->topicname(), $comment->id());
        $this->assertEquals($comment, $found);
    }

    public function testUpdatesComment(): void
    {
        $comment = $this->comment();
        $this->sut->insertComment($comment);
        $this->sut->insertComment($this->otherComment());
        $comment = $comment->withMessage("changed comment");
        $this->sut->updateComment($comment);
        $found = $this->sut->findComment($comment->topicname(), $comment->id());
        $this->assertEquals($comment, $found);
    }

    public function testDeletesComment(): void
    {
        $comment = $this->comment();
        $this->sut->insertComment($comment);
        $this->sut->insertComment($this->otherComment());
        $this->sut->deleteComment($comment);
        $found = $this->sut->findComment($comment->topicname(), $comment->id());
        $this->assertNull($found);
    }

    private function comment()
    {
        return new Comment(
            "63fba86870945",
            "topic1",
            1677437048,
            "john",
            "john@example.com",
            "A nice comment",
            false
        );
    }

    private function otherComment()
    {
        return new Comment(
            "63fbd5db93f41",
            "topic1",
            1677448677,
            "jane",
            "jane@example.com",
            "Another comment",
            true
        );
    }
}
