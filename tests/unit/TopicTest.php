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

use org\bovigo\vfs\vfsStreamWrapper;
use org\bovigo\vfs\vfsStreamDirectory;
use org\bovigo\vfs\vfsStream;

class TopicTest extends TestCase
{
    const TOPIC = 'foo';

    /**
     * @var Topic
     */
    protected $subject;

    /**
     * @var string
     */
    protected $filename;

    public function setUp()
    {
        $this->setUpFilesystem();
        $this->subject = new Topic(self::TOPIC);
    }

    protected function setUpFilesystem()
    {
        global $pth;

        vfsStreamWrapper::register();
        vfsStreamWrapper::setRoot(new vfsStreamDirectory('test'));
        $this->filename = vfsStream::url('test/twocents/foo.csv');
        mkdir(dirname($this->filename));
        foreach (array('foo', 'bar', 'baz') as $name) {
            touch(dirname($this->filename) . '/' . $name . '.csv');
        }
        $pth['folder']['content'] = vfsStream::url('test/');
    }

    public function testNameIsCorrect()
    {
        $this->assertEquals(self::TOPIC, $this->subject->getName());
    }

    public function testInsertionCreatesFile()
    {
        $this->subject->delete();
        $this->assertFileNotExists($this->filename);
        $this->subject->insert();
        $this->assertFileExists($this->filename);
    }

    public function testDeletionRemovesFile()
    {
        $this->subject->insert();
        $this->assertFileExists($this->filename);
        $this->subject->delete();
        $this->assertFileNotExists($this->filename);
    }

    public function testFinds3Topics()
    {
        $topics = Topic::findAll();
        $this->assertContainsOnlyInstancesOf('Twocents\\Topic', $topics);
        $this->assertCount(3, $topics);
    }

    public function testFindsCorrectTopic()
    {
        $topic = Topic::findByName(self::TOPIC);
        $this->assertInstanceOf('Twocents\\Topic', $topic);
        $this->assertEquals(self::TOPIC, $topic->getName());
    }

    public function testDoesNotFindNonExistingTopic()
    {
        $this->assertNull(Topic::findByName('unknown'));
    }

    public function testCreatesFolderIfNotExisting()
    {
        global $pth;

        $pth['folder']['content'] = vfsStream::url('test/dummy/');
        Topic::findAll();
        $this->assertFileExists($pth['folder']['content']);
    }
}
