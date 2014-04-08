<?php

/**
 * @version SVN: $Id$
 */

require_once 'vfsStream/vfsStream.php';

require_once './classes/Domain.php';

require_once './classes/DataSource.php';

runkit_function_redefine('ftruncate', '$stream, $pos', '');

class PersisterTest extends PHPUnit_Framework_TestCase
{
    private $_filename;

    /** @var Twocents_Persister */
    private $_subject;

    public function setUp()
    {
        vfsStreamWrapper::register();
        vfsStreamWrapper::setRoot(new vfsStreamDirectory('test'));
        $this->_filename = vfsStream::url('test/twocents.dat');
        $this->_subject = new Twocents_Persister($this->_filename);
    }

    public function testLoadDefaults()
    {
        $this->assertFileNotExists($this->_filename);
        $actual = $this->_subject->load();
        $this->assertEquals(new Twocents_Model(), $actual);
    }

    public function testLoadStored()
    {
        $model = $this->_subject->open();
        $model->addComment('foo', time(), 'cmb', 'lorem ipsum');
        $this->_subject->close();
        $this->assertFileExists($this->_filename);
        $this->assertEquals($model, $this->_subject->load());
    }

    public function testLoadCorruptFile()
    {
        file_put_contents($this->_filename, '');
        $this->assertEquals(new Twocents_Model(), $this->_subject->load());
    }
}

?>
