<?php

/**
 * The main "program".
 *
 * PHP version 5
 *
 * @category  CMSimple_XH
 * @package   Twocents
 * @author    Christoph M. Becker <cmbecker69@gmx.de>
 * @copyright 2014 Christoph M. Becker <http://3-magi.net/>
 * @license   http://www.gnu.org/licenses/gpl-3.0.en.html GNU GPLv3
 * @version   SVN: $Id$
 * @link      http://3-magi.net/?CMSimple_XH/Twocents_XH
 */

/**
 * The data source layer.
 */
require_once $pth['folder']['plugin_classes'] . 'DataSource.php';

/**
 * The presentation layer.
 */
require_once $pth['folder']['plugin_classes'] . 'Presentation.php';

/**
 * The plugin version.
 */
define('TWOCENTS_VERSION', '@TWOCENTS_VERSION@');

/**
 * Renders the comments view and handles related requests.
 *
 * @param string $topicname A topicname.
 *
 * @return string (X)HTML.
 *
 * @global Twocents_Controller The plugin controller.
 */
function twocents($topicname)
{
    global $_Twocents_controller;

    return $_Twocents_controller->renderComments($topicname);
}

/**
 * The plugin controller.
 *
 * @var Twocents_Controller
 */
$_Twocents_controller = new Twocents_Controller();

?>
