<?php

/**
 * Copyright 2014-2017 Christoph M. Becker
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

use PHPUnit\Framework\TestCase;
use org\bovigo\vfs\vfsStreamWrapper;
use org\bovigo\vfs\vfsStreamDirectory;
use org\bovigo\vfs\vfsStream;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class CommentTest extends TestCase
{
    const ID = '1a2b3c';

    const TOPICNAME = 'foo';

    const TIME = 123456;

    const LINE1 = "1a2b3c,123456,cmb,,,\n";

    const LINE2 = "4d5e6f,234567,john,,\n";

    /**
     * @var Comment
     */
    protected $subject;

    /**
     * @var string
     */
    protected $filename;

    public function setUp(): void
    {
        $this->setUpFilesystem();
        $this->subject = Comment::make(self::TOPICNAME, self::TIME);
        uopz_set_return('uniqid', self::ID);
    }

    protected function tearDown(): void
    {
        uopz_unset_return('uniqid');
    }

    protected function setUpFilesystem()
    {
        global $pth;

        vfsStreamWrapper::register();
        vfsStreamWrapper::setRoot(new vfsStreamDirectory('test'));
        $this->filename = vfsStream::url('test/twocents/foo.csv');
        mkdir(dirname($this->filename));
        file_put_contents($this->filename, self::LINE2);
        $pth['folder']['content'] = vfsStream::url('test/');
    }

    public function testIdIsNull()
    {
        $this->assertNull($this->subject->getId());
    }

    public function testTopicnameIsCorrect()
    {
        $this->assertEquals(self::TOPICNAME, $this->subject->getTopicname());
    }

    public function testTimeIsCorrect()
    {
        $this->assertEquals(self::TIME, $this->subject->getTime());
    }

    public function testUserIsCorrect()
    {
        $user = 'cmb';
        $this->subject->setUser($user);
        $this->assertEquals($user, $this->subject->getUser());
    }

    public function testEmailIsCorrect()
    {
        $email = 'me@example.com';
        $this->subject->setEmail($email);
        $this->assertEquals($email, $this->subject->getEmail());
    }

    public function testMessageIsCorrect()
    {
        $message = 'blah blah';
        $this->subject->setMessage($message);
        $this->assertEquals($message, $this->subject->getMessage());
    }

    public function testIsVisible()
    {
        $this->assertTrue($this->subject->isVisible());
    }

    public function testCanHide()
    {
        $this->subject->hide();
        $this->assertFalse($this->subject->isVisible());
    }

    public function testCanShow()
    {
        $this->subject->show();
        $this->assertTrue($this->subject->isVisible());
    }

    public function testInsertSavesToFile()
    {
        unlink($this->filename);
        rmdir(dirname($this->filename));
        $this->subject->setUser('cmb');
        $this->subject->insert();
        $this->assertStringEqualsFile($this->filename, self::LINE1);
    }

    public function testUpdateSavesToFile()
    {
        $this->subject->insert();
        $this->subject->setUser('cmb');
        $this->subject->update();
        $this->assertStringEqualsFile($this->filename, self::LINE2 . self::LINE1);
    }

    public function testDeleteRemovesFromFile()
    {
        $this->subject->insert();
        $this->subject->delete();
        $this->assertStringEqualsFile($this->filename, self::LINE2);
    }

    public function testFinds2CommentsByTopicname()
    {
        $this->subject->insert();
        $comments = Comment::findByTopicname(self::TOPICNAME);
        $this->assertContainsOnlyInstancesOf('Twocents\\Comment', $comments);
        $this->assertCount(2, $comments);
    }

    public function testFindsNoCommentsForNotExistingTopicname()
    {
        $comments = Comment::findByTopicname('bar');
        $this->assertEmpty($comments);
    }

    public function testFindsInsertedComment()
    {
        $this->subject->insert();
        $this->assertEquals($this->subject, Comment::find(self::ID, self::TOPICNAME));
    }

    public function testDoesNotFindDeletedComment()
    {
        $this->subject->insert();
        $this->subject->delete();
        $this->assertNull(Comment::find(self::ID, self::TOPICNAME));
    }
}
