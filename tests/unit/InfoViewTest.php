<?php

/**
 * Testing the info view.
 *
 * PHP version 5
 *
 * @category  Testing
 * @package   Twocents
 * @author    Christoph M. Becker <cmbecker69@gmx.de>
 * @copyright 2014-2017 Christoph M. Becker <http://3-magi.net>
 * @license   http://www.gnu.org/licenses/gpl-3.0.en.html GNU GPLv3
 * @link      http://3-magi.net/?CMSimple_XH/Twocents_XH
 */

/**
 * Testing the info view.
 *
 * @category Testing
 * @package  Twocents
 * @author   Christoph M. Becker <cmbecker69@gmx.de>
 * @license  http://www.gnu.org/licenses/gpl-3.0.en.html GNU GPLv3
 * @link     http://3-magi.net/?CMSimple_XH/Twocents_XH
 */
class InfoViewTest extends TestCase
{
    /**
     * The subject under test.
     *
     * @var Twocents_Controller
     */
    protected $subject;

    /**
     * Sets up the test fixture.
     *
     * @return void
     *
     * @global string Whether the plugin administration is requested.
     * @global string The (X)HTML of the contents area.
     * @global array  The paths of system files and folders.
     * @global array  The localization of the plugins.
     */
    public function setUp()
    {
        global $twocents, $o, $pth, $plugin_tx;

        $this->defineConstant('XH_ADM', true);
        $this->defineConstant('TWOCENTS_VERSION', '1.0');
        $twocents = 'true';
        $o = '';
        $pth = array(
            'folder' => array('plugins' => './plugins/')
        );
        $plugin_tx = array(
            'twocents' => array('alt_icon' => 'Speech bubble')
        );
        $this->subject = new Twocents_Controller();
        $rspmiMock = new PHPUnit_Extensions_MockFunction(
            'XH_registerStandardPluginMenuItems', $this->subject
        );
        $printPluginAdminMock = new PHPUnit_Extensions_MockFunction(
            'print_plugin_admin', $this->subject
        );
        $this->subject->dispatch();
    }

    /**
     * Tests that the heading is rendered.
     *
     * @return void
     *
     * @global string The (X)HTML of the contents area.
     */
    public function testRendersHeading()
    {
        global $o;

        @$this->assertTag(
            array(
                'tag' => 'h1',
                'content' => 'Twocents'
            ),
            $o
        );
    }

    /**
     * Tests that the plugin icon is rendered.
     *
     * @return void
     *
     * @global string The (X)HTML of the contents area.
     */
    public function testRendersIcon()
    {
        global $o;

        @$this->assertTag(
            array(
                'tag' => 'img',
                'attributes' => array(
                    'src' => './plugins/twocents/twocents.png',
                    'class' => 'twocents_icon',
                    'alt' => 'Speech bubble'
                )
            ),
            $o
        );
    }

    /**
     * Tests that the version info is rendered.
     *
     * @return void
     *
     * @global string The (X)HTML of the contents area.
     */
    public function testRendersVersion()
    {
        global $o;

        @$this->assertTag(
            array(
                'tag' => 'p',
                'content' => 'Version: ' . TWOCENTS_VERSION
            ),
            $o
        );
    }

    /**
     * Tests that the copyright info is rendered.
     *
     * @return void
     *
     * @global string The (X)HTML of the contents area.
     */
    public function testRendersCopyright()
    {
        global $o;

        @$this->assertTag(
            array(
                'tag' => 'p',
                'content' => "Copyright \xC2\xA9 2014",
                'child' => array(
                    'tag' => 'a',
                    'attributes' => array(
                        'href' => 'http://3-magi.net/',
                        'target' => '_blank'
                    ),
                    'content' => 'Christoph M. Becker'
                )
            ),
            $o
        );
    }

    /**
     * Tests that the license info is rendered.
     *
     * @return void
     *
     * @global string The (X)HTML of the contents area.
     */
    public function testRendersLicense()
    {
        global $o;

        @$this->assertTag(
            array(
                'tag' => 'p',
                'attributes' => array('class' => 'twocents_license'),
                'content' => 'This program is free software:'
            ),
            $o
        );
    }
}

?>
