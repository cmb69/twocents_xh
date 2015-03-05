<?php

/**
 * The comments views.
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
 * The comments views.
 *
 * @category CMSimple_XH
 * @package  Twocents
 * @author   Christoph M. Becker <cmbecker69@gmx.de>
 * @license  http://www.gnu.org/licenses/gpl-3.0.en.html GNU GPLv3
 * @link     http://3-magi.net/?CMSimple_XH/Twocents_XH
 */
class Twocents_CommentsView
{
    /**
     * Makes and returns a new comments view.
     *
     * @param array            $comments       An array of comments.
     * @param Twocents_Comment $currentComment The current comment.
     * @param string           $messages       (X)HTML messages.
     *
     * @return Twocents_CommentsView.
     */
    public static function make(
        $comments, Twocents_Comment $currentComment = null, $messages = ''
    ) {
        return new self($comments, $currentComment, $messages);
    }

    /**
     * The comments.
     *
     * @var array
     */
    protected $comments;

    /**
     * The current comment, if any.
     *
     * @var Twocents_Comment
     */
    protected $currentComment;

    /**
     * (X)HTML messages.
     *
     * @var string
     */
    protected $messages;

    /**
     * Initializes a new instance.
     *
     * @param array            $comments       An array of comments.
     * @param Twocents_Comment $currentComment The current comment.
     * @param string           $messages       (X)HTML messages.
     *
     * @return void
     */
    protected function __construct(
        $comments, Twocents_Comment $currentComment = null, $messages = ''
    ) {
        $this->comments = (array) $comments;
        $this->currentComment = $currentComment;
        $this->messages = (string) $messages;
    }

    /**
     * Renders the view.
     *
     * @return string (X)HTML.
     */
    public function render()
    {
        $this->writeScriptsToBjs();
        $html = '<div class="twocents_comments">';
        foreach ($this->comments as $comment) {
            if ($comment->isVisible() || XH_ADM) {
                if (isset($this->currentComment)
                    && $this->currentComment->getId() == $comment->getId()
                ) {
                    $html .= $this->messages;
                }
                $view = new Twocents_CommentView($comment, $this->currentComment);
                $html .= $view->render();
            }
        }
        $html .= '</div>';
        if (!isset($this->currentComment)
            || $this->currentComment->getId() == null
        ) {
            $view = new Twocents_CommentFormView($this->currentComment);
            $html .= $this->messages . $view->render();
        }
        return $html;
    }

    /**
     * Writes the scripts to $bjs.
     *
     * @return void
     *
     * @global array  The paths of system files and folders.
     * @global string The (X)HTML fragment to insert at the bottom of the body.
     * @global array  The localization of the plugins.
     */
    protected function writeScriptsToBjs()
    {
        global $pth, $bjs, $plugin_cf, $plugin_tx;

        $config = array();
        foreach (array('comments_markup') as $property) {
            $config[$property] = $plugin_cf['twocents'][$property];
        }
        foreach (array('label_new', 'message_delete') as $property) {
            $config[$property] = $plugin_tx['twocents'][$property];
        }
        $json = XH_encodeJson($config);
        $filename = $pth['folder']['plugins'] . 'twocents/twocents.js';
        $bjs .= <<<EOT
<script type="text/javascript">/* <[CDATA[ */TWOCENTS = $json;/* ]]> */</script>
<script type="text/javascript" src="$filename"></script>
EOT;
    }

}

?>
