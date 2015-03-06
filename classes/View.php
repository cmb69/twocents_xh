<?php

/**
 * The views.
 *
 * PHP version 5
 *
 * @category  CMSimple_XH
 * @package   Twocents
 * @author    Christoph M. Becker <cmbecker69@gmx.de>
 * @copyright 2014-2015 Christoph M. Becker <http://3-magi.net/>
 * @license   http://www.gnu.org/licenses/gpl-3.0.en.html GNU GPLv3
 * @link      http://3-magi.net/?CMSimple_XH/Twocents_XH
 */

/**
 * The views.
 *
 * @category CMSimple_XH
 * @package  Twocents
 * @author   Christoph M. Becker <cmbecker69@gmx.de>
 * @license  http://www.gnu.org/licenses/gpl-3.0.en.html GNU GPLv3
 * @link     http://3-magi.net/?CMSimple_XH/Twocents_XH
 */
abstract class Twocents_View
{
    /**
     * Returns the URL to post or link to.
     *
     * @return string
     *
     * @global string The script name.
     */
    protected function getUrl()
    {
        global $sn;

        $queryString = preg_replace(
            '/&twocents_id=[^&]+/', '', $_SERVER['QUERY_STRING']
        );
        return $sn . '?' . $queryString;
    }
}

?>
