<?php

/**
 * The presentation layer.
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
 * The controllers.
 *
 * @category CMSimple_XH
 * @package  Twocents
 * @author   Christoph M. Becker <cmbecker69@gmx.de>
 * @license  http://www.gnu.org/licenses/gpl-3.0.en.html GNU GPLv3
 * @link     http://3-magi.net/?CMSimple_XH/Twocents_XH
 */
class Twocents_Controller
{
    /**
     * The current comment, if any.
     *
     * @var Twocents_Comment
     */
    private $_comment;

    /**
     * Renders the comments on a certain topic.
     *
     * @param string $topicname A topicname.
     *
     * @return string (X)HTML.
     */
    public function renderComments($topicname)
    {
        $action = isset($_POST['twocents_action'])
            ? stsl($_POST['twocents_action']) : '';
        $html = '';
        switch ($action) {
        case 'add_comment':
            $html .= $this->_addComment($topicname);
            break;
        case 'update_comment':
            if (XH_ADM) {
                $html .= $this->_updateComment($topicname);
            }
            break;
        case 'remove_comment':
            if (XH_ADM) {
                $this->_deleteComment($topicname);
            }
            break;
        }
        if (isset($_GET['twocents_id'])) {
            $this->_comment = Twocents_Comment::find(
                stsl($_GET['twocents_id']), $topicname
            );
        }
        $comments = Twocents_Comment::findByTopicname($topicname);
        $html .= Twocents_CommentsView::make($comments, $this->_comment)->render();
        return $html;
    }

    /**
     * Adds a comment and returns error messages.
     *
     * @param string $topicname A topicname.
     *
     * @return string (X)HTML.
     */
    private function _addComment($topicname)
    {
        $this->_comment = Twocents_Comment::make(
            $topicname, time()
        );
        $this->_comment->setUser(trim(stsl($_POST['twocents_user'])));
        $this->_comment->setEmail(trim(stsl($_POST['twocents_email'])));
        $this->_comment->setMessage(trim(stsl($_POST['twocents_message'])));
        $html = $this->_renderErrorMessages();
        if (!$html) {
            $this->_comment->insert();
            $this->_comment = null;
        }
        return $html;
    }

    /**
     * Updates a comment and returns error messages.
     *
     * @param string $topicname A topicname.
     *
     * @return string (X)HTML.
     */
    private function _updateComment($topicname)
    {
        $this->_comment = Twocents_Comment::find(
            stsl($_POST['twocents_id']), $topicname
        );
        $this->_comment->setUser(trim(stsl($_POST['twocents_user'])));
        $this->_comment->setEmail(trim(stsl($_POST['twocents_email'])));
        $this->_comment->setMessage(trim(stsl($_POST['twocents_message'])));
        $html = $this->_renderErrorMessages();
        if (!$html) {
            $this->_comment->update();
            $this->_comment = null;
        }
        return $html;
    }

    /**
     * Deletes a comment.
     *
     * @param string $topicname A topicname.
     *
     * @return void
     */
    private function _deleteComment($topicname)
    {
        $comment = Twocents_Comment::find(
            stsl($_POST['twocents_id']), $topicname
        );
        $comment->delete();
    }

    /**
     * Renders error messages.
     *
     * @return string (X)HTML.
     *
     * @global array The localization of the plugins.
     */
    private function _renderErrorMessages()
    {
        global $plugin_tx;

        $html = '';
        if ($this->_comment->getUser() == '') {
            $html .= XH_message('fail', $plugin_tx['twocents']['error_user']);
        }
        if ($this->_comment->getEmail() == '') {
            $html .= XH_message('fail', $plugin_tx['twocents']['error_email']);
        }
        if ($this->_comment->getMessage() == '') {
            $html .= XH_message('fail', $plugin_tx['twocents']['error_message']);
        }
        return $html;
    }
}

/**
 * The comments views..
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
     *
     * @return Twocents_CommentsView.
     */
    public static function make($comments, Twocents_Comment $currentComment = null)
    {
        return new self($comments, $currentComment);
    }

    /**
     * The comments.
     *
     * @var array
     */
    private $_comments;

    /**
     * The current comment, if any.
     *
     * @var Twocents_Comment
     */
    private $_currentComment;

    /**
     * Initializes a new instance.
     *
     * @param array            $comments       An array of comments.
     * @param Twocents_Comment $currentComment The current comment.
     *
     * @return void
     */
    private function __construct($comments, Twocents_Comment $currentComment = null)
    {
        $this->_comments = (array) $comments;
        $this->_currentComment = $currentComment;
    }

    /**
     * Renders the view.
     *
     * @return string (X)HTML.
     *
     * @global array  The paths of system files and folders.
     * @global string The (X)HTML fragment to insert at the bottom of the body.
     */
    public function render()
    {
        global $pth, $bjs;

        $bjs .= '<script type="text/javascript" src="' . $pth['folder']['plugins']
            . 'twocents/twocents.js"></script>';
        $html = '<ul class="twocents_comments">';
        foreach ($this->_comments as $comment) {
            $html .= $this->_renderComment($comment);
        }
        $html .= '</ul>';
        if (!isset($this->_currentComment)
            || $this->_currentComment->getId() == null
        ) {
            $html .= $this->_renderCommentForm($this->_currentComment);
        }
        return $html;
    }

    /**
     * Renders a certain comment.
     *
     * @param Twocents_Comment $comment A comment.
     *
     * @return string (X)HTML.
     */
    private function _renderComment(Twocents_Comment $comment)
    {
        $html = '<li>';
        if (isset($this->_currentComment)
            && $this->_currentComment->getId() == $comment->getId()
        ) {
            $html .= $this->_renderCommentForm($this->_currentComment);
        } else {
            if (XH_ADM) {
                $html .= $this->_renderAdminTools($comment);
            }
            $html .= '<p>' . $this->_renderHeading($comment) . '</p>'
                . '<blockquote>' . XH_hsc($comment->getMessage()) . '</blockquote>';
        }
        $html .= '</li>';
        return $html;
    }

    /**
     * Renders the admin tools.
     *
     * @param Twocents_Comment $comment A comment.
     *
     * @return string (X)HTML.
     */
    private function _renderAdminTools(Twocents_Comment $comment)
    {
        return '<div class="twocents_admin_tools">'
            . $this->_renderEditLink($comment)
            . $this->_renderDeleteForm($comment)
            . '</div>';
    }

    /**
     * Renders the delete form.
     *
     * @param Twocents_Comment $comment A comment.
     *
     * @return string (X)HTML.
     *
     * @global array The localization of the plugins.
     */
    private function _renderDeleteForm(Twocents_Comment $comment)
    {
        global $plugin_tx;

        return '<form method="post" action="' . XH_hsc($this->_getUrl()) . '">'
            . tag(
                'input type="hidden" name="twocents_id" value="'
                . $comment->getId() . '"'
            )
            . '<button name="twocents_action" value="remove_comment">'
            . $plugin_tx['twocents']['label_delete']
            . '</button>'
            . '</form>';
    }

    /**
     * Renders the edit link.
     *
     * @param Twocents_Comment $comment A comment.
     *
     * @return string (X)HTML.
     *
     * @global array The localization of the plugins.
     */
    private function _renderEditLink(Twocents_Comment $comment)
    {
        global $plugin_tx;

        $url = $this->_getUrl() . '&twocents_id=' . $comment->getId();
        return '<a href="' . XH_hsc($url) . '">'
            . $plugin_tx['twocents']['label_edit'] . '</a>';
    }

    /**
     * Renders the comment heading.
     *
     * @param Twocents_Comment $comment A comment.
     *
     * @return string (X)HTML.
     *
     * @global array The localization of the plugins.
     */
    private function _renderHeading(Twocents_Comment $comment)
    {
        global $plugin_tx;

        $date = date($plugin_tx['twocents']['format_date'], $comment->getTime());
        $time = date($plugin_tx['twocents']['format_time'], $comment->getTime());
        return strtr(
            $plugin_tx['twocents']['format_heading'],
            array(
                '{DATE}' => $date,
                '{TIME}' => $time,
                '{USER}' => XH_hsc($comment->getUser())
            )
        );
    }

    /**
     * Renders a comment form.
     *
     * @param Twocents_Comment $comment A comment.
     *
     * @return string (X)HTML.
     *
     * @global array The localization of the plugins.
     */
    private function _renderCommentForm(Twocents_Comment $comment = null)
    {
        global $plugin_tx;

        if (!isset($comment)) {
            $comment = Twocents_Comment::make(null, null);
        }
        return '<form class="twocents_form" method="post" action="'
            . XH_hsc($this->_getUrl()) . '">'
            . tag(
                'input type="hidden" name="twocents_id" value="'
                . XH_hsc($comment->getId()) . '"'
            )
            . '<label><span>' . $plugin_tx['twocents']['label_user'] . '</span>'
            . tag(
                'input type="text" name="twocents_user" value="'
                . XH_hsc($comment->getUser()) . '"'
            )
            . '</label>'
            . '<label><span>' . $plugin_tx['twocents']['label_email'] . '</span>'
            . tag(
                'input type="text" name="twocents_email" value="'
                . XH_hsc($comment->getEmail()) . '"'
            )
            . '</label>'
            . '<label><span>' . $plugin_tx['twocents']['label_message']. '</span>'
            . '<textarea name="twocents_message">'
            . XH_hsc($comment->getMessage())
            . '</textarea></label>'
            . '<div class="twocents_form_buttons">'
            . $this->_renderButtons($comment) . '</div>'
            . '</form>';
    }

    /**
     * Renders the form buttons.
     *
     * @param Twocents_Comment $comment A comment.
     *
     * @return string (X)HTML.
     *
     * @global array The localization of the plugins.
     */
    private function _renderButtons(Twocents_Comment $comment)
    {
        global $plugin_tx;

        $action = $comment->getId() ? 'update' : 'add';
        $html = '<button name="twocents_action" value="' . $action . '_comment">'
            . $plugin_tx['twocents']['label_' . $action] . '</button>';
        if ($comment->getId()) {
            $html .= '<a href="' . $this->_getUrl() . '">Cancel</a>';
        }
        $html .= '<button type="reset">' . $plugin_tx['twocents']['label_reset']
            . '</button>';
        return $html;
    }

    /**
     * Returns the URL to post or link to.
     *
     * @return string
     *
     * @global string The script name.
     */
    private function _getUrl()
    {
        global $sn;

        $queryString = preg_replace(
            '/&twocents_id=[^&]+/', '', $_SERVER['QUERY_STRING']
        );
        return $sn . '?' . $queryString;
    }
}

?>
