<?php

/**
 * The Realblog_XH bridge.
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
 * The Realblog_XH bridge.
 *
 * @category CMSimple_XH
 * @package  Twocents
 * @author   Christoph M. Becker <cmbecker69@gmx.de>
 * @license  http://www.gnu.org/licenses/gpl-3.0.en.html GNU GPLv3
 * @link     http://3-magi.net/?CMSimple_XH/Twocents_XH
 */
class Twocents_RealblogBridge implements Realblog_CommentsBridge
{
    /**
     * Returns the number of comments on a certain topic.
     *
     * @param string $topic A topic ID.
     *
     * @return int
     */
    static public function count($topic)
    {
        return count(Twocents_Comment::findByTopicname($topic));
    }

    /**
     * Handles the comment functionality of a certain topic.
     *
     * @param string $topic A topic ID.
     *
     * @return string (X)HTML.
     */
    static public function handle($topic)
    {
        global $_Twocents_controller;

        return $_Twocents_controller->renderComments($topic);
    }

    /**
     * Returns false, as there is no sensible URL for editing the comments.
     *
     * @param string $topic A topic ID.
     *
     * @return false
     */
    static public function getEditUrl($topic)
    {
        return false;
    }
}

?>
