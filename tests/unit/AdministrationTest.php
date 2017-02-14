<?php

/**
 * Testing the general plugin administration.
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
 * Testing the general plugin administration.
 *
 * @category Testing
 * @package  Twocents
 * @author   Christoph M. Becker <cmbecker69@gmx.de>
 * @license  http://www.gnu.org/licenses/gpl-3.0.en.html GNU GPLv3
 * @link     http://3-magi.net/?CMSimple_XH/Twocents_XH
 */
class AdministrationTest extends TestCase
{
    /**
     * The test subject.
     *
     * @var Twocents_Controller
     */
    protected $subject;

    /**
     * The XH_registerStandardPluginMenuItems() mock.
     *
     * @var object
     */
    protected $rspmiMock;

    /**
     * Sets up the test fixture.
     *
     * @return void
     *
     * @global string Whether the plugin administration is requested.
     * @global string The value of the <var>admin</var> GP parameter.
     * @global string The value of the <var>action</var> GP parameter.
     */
    public function setUp()
    {
        global $twocents, $admin, $action;

        $this->defineConstant('XH_ADM', true);
        $twocents = 'true';
        $admin = 'plugin_stylesheet';
        $action = 'plugin_text';
        $this->subject = new Twocents_Controller();
        $this->rspmiMock = new PHPUnit_Extensions_MockFunction(
            'XH_registerStandardPluginMenuItems', $this->subject
        );
        $printPluginAdminMock = new PHPUnit_Extensions_MockFunction(
            'print_plugin_admin', $this->subject
        );
        $printPluginAdminMock->expects($this->once())->with('on');
        $pluginAdminCommonMock = new PHPUnit_Extensions_MockFunction(
            'plugin_admin_common', $this->subject
        );
        $pluginAdminCommonMock->expects($this->once())
            ->with($action, $admin, 'twocents');
    }

    /**
     * Tests that the integrated plugin menu is shown.
     *
     * @return void
     */
    public function testShowsIntegratedPluginMenu()
    {
        $this->rspmiMock->expects($this->once())->with(true);
        $this->subject->dispatch();
    }

    /**
     * Tests the stylesheet administration.
     *
     * @return void
     */
    public function testStylesheet()
    {
        $this->subject->dispatch();
    }
}

?>
