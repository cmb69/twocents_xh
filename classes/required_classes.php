<?php

/**
 * The autoloader.
 *
 * PHP version 5
 *
 * @category  CMSimple_XH
 * @package   Twocents
 * @author    Christoph M. Becker <cmbecker69@gmx.de>
 * @copyright 2015-2017 Christoph M. Becker <http://3-magi.net>
 * @license   http://www.gnu.org/licenses/gpl-3.0.en.html GNU GPLv3
 * @link      http://3-magi.net/?CMSimple_XH/Twocents_XH
 */

spl_autoload_register(function ($class) {
    global $pth;

    $parts = explode('\\', $class, 2);
    if ($parts[0] == 'Twocents') {
        include_once $pth['folder']['plugins'] . 'twocents/classes/'
            . $parts[1] . '.php';
    }
});
