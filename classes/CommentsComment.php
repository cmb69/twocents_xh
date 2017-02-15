<?php

/**
 * The comments of the Comments plugin.
 *
 * PHP version 5
 *
 * @category  CMSimple_XH
 * @package   Twocents
 * @author    Christoph M. Becker <cmbecker69@gmx.de>
 * @copyright 2014-2017 Christoph M. Becker <http://3-magi.net/>
 * @license   http://www.gnu.org/licenses/gpl-3.0.en.html GNU GPLv3
 * @link      http://3-magi.net/?CMSimple_XH/Twocents_XH
 */

namespace Twocents;

/**
 * The comments of the Comments plugin.
 *
 * @category CMSimple_XH
 * @package  Twocents
 * @author   Christoph M. Becker <cmbecker69@gmx.de>
 * @license  http://www.gnu.org/licenses/gpl-3.0.en.html GNU GPLv3
 * @link     http://3-magi.net/?CMSimple_XH/Twocents_XH
 */
class CommentsComment extends Comment
{
    /**
     * The file extension.
     */
    const EXT = 'txt';

    /**
     * Finds all comments for a certain topic and returns them.
     *
     * @param string $name A topicname.
     *
     * @return array
     */
    public static function findByTopicname($name)
    {
        $comments = array();
        Db::lock(LOCK_SH);
        $filename = Db::getFoldername() . $name . '.' . self::EXT;
        if (is_readable($filename) && ($file = fopen($filename, 'r'))) {
            if (fgets($file) !== false) {
                while (($line = fgets($file)) !== false) {
                    $record = explode('-,+;-', trim($line));
                    $comments[] = self::load($name, $record);
                }
            }
            fclose($file);
        }
        Db::lock(LOCK_UN);
        return $comments;
    }

    /**
     * Loads a comment and returns it.
     *
     * @param string $topicname A topicname.
     * @param array  $record    A record.
     *
     * @return Comment
     */
    protected static function load($topicname, $record)
    {
        // image is $record[6]
        $comment = new parent($topicname, $record[5]);
        $comment->user = $record[1];
        $comment->email = $record[2];
        $comment->message = $record[7];
        $comment->hidden = $record[5] == 'hidden';
        return $comment;
    }
}

?>
