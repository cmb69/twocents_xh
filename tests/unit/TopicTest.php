<?php

/**
 * Testing the topics.
 *
 * PHP version 5
 *
 * @category  Testing
 * @package   Twocents
 * @author    Christoph M. Becker <cmbecker69@gmx.de>
 * @copyright 2014-2015 Christoph M. Becker <http://3-magi.net/>
 * @license   http://www.gnu.org/licenses/gpl-3.0.en.html GNU GPLv3
 * @link      http://3-magi.net/?CMSimple_XH/Twocents_XH
 */

require_once './vendor/autoload.php';

use org\bovigo\vfs\vfsStreamWrapper;
use org\bovigo\vfs\vfsStreamDirectory;
use org\bovigo\vfs\vfsStream;

/**
 * Testing the topics.
 *
 * @category CMSimple_XH
 * @package  Twocents
 * @author   Christoph M. Becker <cmbecker69@gmx.de>
 * @license  http://www.gnu.org/licenses/gpl-3.0.en.html GNU GPLv3
 * @link     http://3-magi.net/?CMSimple_XH/Twocents_XH
 *
 * @todo Test via controller.
 */
class TopicTest extends PHPUnit_Framework_TestCase
{
    /**
     * The topic name.
     */
    const TOPIC = 'foo';

    /**
     * The test subject.
     *
     * @var Twocents_Topic
     */
    private $_subject;

    /**
     * The path of the data file.
     *
     * @var string
     */
    private $_filename;

    /**
     * Sets up the test fixture.
     *
     * @return void
     */
    public function setUp()
    {
        $this->_setUpFilesystem();
        $this->_subject = new Twocents_Topic(self::TOPIC);
    }

    /**
     * Sets up the test filesystem.
     *
     * @return void
     *
     * @global array The paths of system files and folders.
     */
    private function _setUpFilesystem()
    {
        global $pth;

        vfsStreamWrapper::register();
        vfsStreamWrapper::setRoot(new vfsStreamDirectory('test'));
        $this->_filename = vfsStream::url('test/twocents/foo.csv');
        mkdir(dirname($this->_filename));
        foreach (array('foo', 'bar', 'baz') as $name) {
            touch(dirname($this->_filename) . '/' . $name . '.csv');
        }
        $pth['folder']['content'] = vfsStream::url('test/');
    }

    /**
     * Tests that the topicname is correct.
     *
     * @return void
     */
    public function testNameIsCorrect()
    {
        $this->assertEquals(self::TOPIC, $this->_subject->getName());
    }

    /**
     * Tests that insert() creates the data file.
     *
     * @return void
     */
    public function testInsertionCreatesFile()
    {
        $this->_subject->delete();
        $this->assertFileNotExists($this->_filename);
        $this->_subject->insert();
        $this->assertFileExists($this->_filename);
    }

    /**
     * Tests that delete() removes the data file.
     *
     * @return void
     */
    public function testDeletionRemovesFile()
    {
        $this->_subject->insert();
        $this->assertFileExists($this->_filename);
        $this->_subject->delete();
        $this->assertFileNotExists($this->_filename);
    }

    /**
     * Tests that 3 topics are found.
     *
     * @return void
     */
    public function testFinds3Topics()
    {
        $topics = Twocents_Topic::findAll();
        $this->assertContainsOnlyInstancesOf('Twocents_Topic', $topics);
        $this->assertCount(3, $topics);
    }

    /**
     * Tests that the correct topic is found.
     *
     * @return void
     */
    public function testFindsCorrectTopic()
    {
        $topic = Twocents_Topic::findByName(self::TOPIC);
        $this->assertInstanceOf('Twocents_Topic', $topic);
        $this->assertEquals(self::TOPIC, $topic->getName());
    }

    /**
     * Tests that a non existing topic is not found.
     *
     * @return void
     */
    public function testDoesNotFindNonExistingTopic()
    {
        $this->assertNull(Twocents_Topic::findByName('unknown'));
    }

    /**
     * Tests that the data folder is created if it doesn't exist.
     *
     * @return void
     *
     * @global array The paths of system files and folders.
     */
    public function testCreatesFolderIfNotExisting()
    {
        global $pth;

        $pth['folder']['content'] = vfsStream::url('test/dummy/');
        Twocents_Topic::findAll();
        $this->assertFileExists($pth['folder']['content']);
    }
}

?>
