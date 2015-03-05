<?php

/**
 * Testing the comments.
 *
 * PHP version 5
 *
 * @category  Testing
 * @package   Twocents
 * @author    Christoph M. Becker <cmbecker69@gmx.de>
 * @copyright 2014 Christoph M. Becker <http://3-magi.net/>
 * @license   http://www.gnu.org/licenses/gpl-3.0.en.html GNU GPLv3
 * @link      http://3-magi.net/?CMSimple_XH/Twocents_XH
 */

require_once './vendor/autoload.php';
require_once './classes/DataSource.php';

use org\bovigo\vfs\vfsStreamWrapper;
use org\bovigo\vfs\vfsStreamDirectory;
use org\bovigo\vfs\vfsStream;

/**
 * Testing the comments.
 *
 * @category CMSimple_XH
 * @package  Twocents
 * @author   Christoph M. Becker <cmbecker69@gmx.de>
 * @license  http://www.gnu.org/licenses/gpl-3.0.en.html GNU GPLv3
 * @link     http://3-magi.net/?CMSimple_XH/Twocents_XH
 */
class CommentTest extends PHPUnit_Framework_TestCase
{
    /**
     * The comment ID.
     */
    const ID = '1a2b3c';

    /**
     * The comment topicname.
     */
    const TOPICNAME = 'foo';

    /**
     * The comment timestamp.
     */
    const TIME = 123456;

    /**
     * A CSV line.
     */
    const LINE1 = "1a2b3c,123456,cmb,,,\n";

    /**
     * Another CSV line.
     */
    const LINE2 = "4d5e6f,234567,john,,\n";

    /**
     * The test subject.
     *
     * @var Twocents_Comment
     */
    private $_subject;

    /**
     * The comments filename.
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
        $this->_subject = Twocents_Comment::make(self::TOPICNAME, self::TIME);
        $this->_setupMocks();
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
        file_put_contents($this->_filename, self::LINE2);
        $pth['folder']['content'] = vfsStream::url('test/');
    }

    /**
     * Sets up the test mocks.
     *
     * @return void
     */
    private function _setUpMocks()
    {
        $uniqidStub = new PHPUnit_Extensions_MockFunction(
            'uniqid', $this->_subject
        );
        $uniqidStub->expects($this->any())->will($this->returnValue(self::ID));
    }

    /**
     * Tests that the ID is null.
     *
     * @return void
     */
    public function testIdIsNull()
    {
        $this->assertNull($this->_subject->getId());
    }

    /**
     * Tests that the topicname is correct.
     *
     * @return void
     */
    public function testTopicnameIsCorrect()
    {
        $this->assertEquals(self::TOPICNAME, $this->_subject->getTopicname());
    }

    /**
     * Tests that the timestamp is correct.
     *
     * @return void
     */
    public function testTimeIsCorrect()
    {
        $this->assertEquals(self::TIME, $this->_subject->getTime());
    }

    /**
     * Tests that the username is correct.
     *
     * @return void
     */
    public function testUserIsCorrect()
    {
        $user = 'cmb';
        $this->_subject->setUser($user);
        $this->assertEquals($user, $this->_subject->getUser());
    }

    /**
     * Tests that the email address is correct.
     *
     * @return void
     */
    public function testEmailIsCorrect()
    {
        $email = 'me@example.com';
        $this->_subject->setEmail($email);
        $this->assertEquals($email, $this->_subject->getEmail());
    }

    /**
     * Tests that the comment message is correct.
     *
     * @return void
     */
    public function testMessageIsCorrect()
    {
        $message = 'blah blah';
        $this->_subject->setMessage($message);
        $this->assertEquals($message, $this->_subject->getMessage());
    }

    /**
     * Tests that the comment is visible.
     *
     * @return void
     */
    public function testIsVisible()
    {
        $this->assertTrue($this->_subject->isVisible());
    }

    /**
     * Tests that the comment can be hidden.
     *
     * @return void
     */
    public function testCanHide()
    {
        $this->_subject->hide();
        $this->assertFalse($this->_subject->isVisible());
    }

    /**
     * Tests that the comment can be shown.
     *
     * @return void
     */
    public function testCanShow()
    {
        $this->_subject->show();
        $this->assertTrue($this->_subject->isVisible());
    }

    /**
     * Tests that insert() saves the comment to the data file.
     *
     * @return void
     */
    public function testInsertSavesToFile()
    {
        unlink($this->_filename);
        rmdir(dirname($this->_filename));
        $this->_subject->setUser('cmb');
        $this->_subject->insert();
        $this->assertStringEqualsFile($this->_filename, self::LINE1);
    }

    /**
     * Tests that update() saves the comment to the data file.
     *
     * @return void
     */
    public function testUpdateSavesToFile()
    {
        $this->_subject->insert();
        $this->_subject->setUser('cmb');
        $this->_subject->update();
        $this->assertStringEqualsFile($this->_filename, self::LINE2 . self::LINE1);
    }

    /**
     * Tests that delete() removes the comment from the data file.
     *
     * @return void
     */
    public function testDeleteRemovesFromFile()
    {
        $this->_subject->insert();
        $this->_subject->delete();
        $this->assertStringEqualsFile($this->_filename, self::LINE2);
    }

    /**
     * Tests that 2 comments are found by topicname.
     *
     * @return void
     */
    public function testFinds2CommentsByTopicname()
    {
        $this->_subject->insert();
        $comments = Twocents_Comment::findByTopicname(self::TOPICNAME);
        $this->assertContainsOnlyInstancesOf('Twocents_Comment', $comments);
        $this->assertCount(2, $comments);
    }

    /**
     * Tests that no comments are found for a not existing topicname.
     *
     * @return void
     */
    public function testFindsNoCommentsForNotExistingTopicname()
    {
        $comments = Twocents_Comment::findByTopicname('bar');
        $this->assertEmpty($comments);
    }

    /**
     * Tests that an inserted comment is found.
     *
     * @return void
     */
    public function testFindsInsertedComment()
    {
        $this->_subject->insert();
        $this->assertEquals(
            $this->_subject, Twocents_Comment::find(self::ID, self::TOPICNAME)
        );
    }

    /**
     * Tests that a deleted comment is not found.
     *
     * @return void
     */
    public function testDoesNotFindDeletedComment()
    {
        $this->_subject->insert();
        $this->_subject->delete();
        $this->assertNull(Twocents_Comment::find(self::ID, self::TOPICNAME));
    }
}

?>
