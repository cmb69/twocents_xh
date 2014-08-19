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

/*
 * Prevent direct access and usage from unsupported CMSimple_XH versions.
 */
if (!defined('CMSIMPLE_XH_VERSION')
    || strpos(CMSIMPLE_XH_VERSION, 'CMSimple_XH') !== 0
    || version_compare(CMSIMPLE_XH_VERSION, 'CMSimple_XH 1.6', 'lt')
) {
    header('HTTP/1.1 403 Forbidden');
    header('Content-Type: text/plain; charset=UTF-8');
    die(<<<EOT
Twocents_XH detected an unsupported CMSimple_XH version.
Uninstall Twocents_XH or upgrade to a supported CMSimple_XH version!
EOT
    );
}

/**
 * The service layer.
 */
require_once $pth['folder']['plugin_classes'] . 'Service.php';

/**
 * The data source layer.
 */
require_once $pth['folder']['plugin_classes'] . 'DataSource.php';

/**
 * The presentation layer.
 */
require_once $pth['folder']['plugin_classes'] . 'Presentation.php';

/**
 * The Realblog_XH bridge.
 */
if (interface_exists('Realblog_CommentsBridge')) {
    include_once $pth['folder']['plugin_classes'] . 'RealblogBridge.php';
}

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
$_Twocents_controller->dispatch();

?>
