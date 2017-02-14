<?php

/**
 * Testing the comments.
 *
 * PHP version 5
 *
 * @category  Testing
 * @package   Twocents
 * @author    Christoph M. Becker <cmbecker69@gmx.de>
 * @copyright 2014-2017 Christoph M. Becker <http://3-magi.net/>
 * @license   http://www.gnu.org/licenses/gpl-3.0.en.html GNU GPLv3
 * @link      http://3-magi.net/?CMSimple_XH/Twocents_XH
 */

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
class CommentTest extends TestCase
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
    protected $subject;

    /**
     * The comments filename.
     *
     * @var string
     */
    protected $filename;

    /**
     * Sets up the test fixture.
     *
     * @return void
     */
    public function setUp()
    {
        $this->setUpFilesystem();
        $this->subject = Twocents_Comment::make(self::TOPICNAME, self::TIME);
        $this->setupMocks();
    }

    /**
     * Sets up the test filesystem.
     *
     * @return void
     *
     * @global array The paths of system files and folders.
     */
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

    /**
     * Sets up the test mocks.
     *
     * @return void
     */
    protected function setUpMocks()
    {
        $uniqidStub = new PHPUnit_Extensions_MockFunction(
            'uniqid', $this->subject
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
        $this->assertNull($this->subject->getId());
    }

    /**
     * Tests that the topicname is correct.
     *
     * @return void
     */
    public function testTopicnameIsCorrect()
    {
        $this->assertEquals(self::TOPICNAME, $this->subject->getTopicname());
    }

    /**
     * Tests that the timestamp is correct.
     *
     * @return void
     */
    public function testTimeIsCorrect()
    {
        $this->assertEquals(self::TIME, $this->subject->getTime());
    }

    /**
     * Tests that the username is correct.
     *
     * @return void
     */
    public function testUserIsCorrect()
    {
        $user = 'cmb';
        $this->subject->setUser($user);
        $this->assertEquals($user, $this->subject->getUser());
    }

    /**
     * Tests that the email address is correct.
     *
     * @return void
     */
    public function testEmailIsCorrect()
    {
        $email = 'me@example.com';
        $this->subject->setEmail($email);
        $this->assertEquals($email, $this->subject->getEmail());
    }

    /**
     * Tests that the comment message is correct.
     *
     * @return void
     */
    public function testMessageIsCorrect()
    {
        $message = 'blah blah';
        $this->subject->setMessage($message);
        $this->assertEquals($message, $this->subject->getMessage());
    }

    /**
     * Tests that the comment is visible.
     *
     * @return void
     */
    public function testIsVisible()
    {
        $this->assertTrue($this->subject->isVisible());
    }

    /**
     * Tests that the comment can be hidden.
     *
     * @return void
     */
    public function testCanHide()
    {
        $this->subject->hide();
        $this->assertFalse($this->subject->isVisible());
    }

    /**
     * Tests that the comment can be shown.
     *
     * @return void
     */
    public function testCanShow()
    {
        $this->subject->show();
        $this->assertTrue($this->subject->isVisible());
    }

    /**
     * Tests that insert() saves the comment to the data file.
     *
     * @return void
     */
    public function testInsertSavesToFile()
    {
        unlink($this->filename);
        rmdir(dirname($this->filename));
        $this->subject->setUser('cmb');
        $this->subject->insert();
        $this->assertStringEqualsFile($this->filename, self::LINE1);
    }

    /**
     * Tests that update() saves the comment to the data file.
     *
     * @return void
     */
    public function testUpdateSavesToFile()
    {
        $this->subject->insert();
        $this->subject->setUser('cmb');
        $this->subject->update();
        $this->assertStringEqualsFile($this->filename, self::LINE2 . self::LINE1);
    }

    /**
     * Tests that delete() removes the comment from the data file.
     *
     * @return void
     */
    public function testDeleteRemovesFromFile()
    {
        $this->subject->insert();
        $this->subject->delete();
        $this->assertStringEqualsFile($this->filename, self::LINE2);
    }

    /**
     * Tests that 2 comments are found by topicname.
     *
     * @return void
     */
    public function testFinds2CommentsByTopicname()
    {
        $this->subject->insert();
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
        $this->subject->insert();
        $this->assertEquals(
            $this->subject, Twocents_Comment::find(self::ID, self::TOPICNAME)
        );
    }

    /**
     * Tests that a deleted comment is not found.
     *
     * @return void
     */
    public function testDoesNotFindDeletedComment()
    {
        $this->subject->insert();
        $this->subject->delete();
        $this->assertNull(Twocents_Comment::find(self::ID, self::TOPICNAME));
    }
}

?>
