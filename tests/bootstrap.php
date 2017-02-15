<?php

/**
 * The autoloader.
 *
 * PHP version 5
 *
 * @category  CMSimple_XH
 * @package   Testing
 * @author    Christoph M. Becker <cmbecker69@gmx.de>
 * @copyright 2015-2017 Christoph M. Becker <http://3-magi.net>
 * @license   http://www.gnu.org/licenses/gpl-3.0.en.html GNU GPLv3
 * @link      http://3-magi.net/?CMSimple_XH/Twocents_XH
 */

require_once './vendor/autoload.php';
require_once '../../cmsimple/functions.php';
require_once '../../cmsimple/adminfuncs.php';
require_once '../utf8/utf8.php';
require_once '../../cmsimple/classes/CSRFProtection.php';
require_once './tests/TestCase.php';


spl_autoload_register(
    function ($class) {
        global $pth;

        $parts = explode('\\', $class, 2);
        if ($parts[0] == 'Twocents') {
            include_once './classes/' . $parts[1] . '.php';
        }
    }
);
