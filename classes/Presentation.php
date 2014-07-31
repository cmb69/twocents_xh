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
     * Dispatches according to the request.
     *
     * @return void
     *
     * @global string Whether the plugin administration is requested.
     */
    public function dispatch()
    {
        global $twocents;

        if (XH_ADM) {
            if (function_exists('XH_registerStandardPluginMenuItems')) {
                XH_registerStandardPluginMenuItems(false);
            }
            if (isset($twocents) && $twocents == 'true') {
                $this->_handleAdministration();
            }
        }
    }

    /**
     * Handles the plugin administration.
     *
     * @return void
     *
     * @global string The value of the <var>admin</var> GP parameter.
     * @global string The value of the <var>action</var> GP parameter.
     * @global string The (X)HTML fragment to insert into the contents area.
     */
    private function _handleAdministration()
    {
        global $admin, $action, $o;

        $o .= print_plugin_admin('off');
        switch ($admin) {
        case '':
            $o .= $this->_renderInfo();
            break;
        default:
            $o .= plugin_admin_common($action, $admin, 'twocents');
        }
    }

    /**
     * Renders the plugin info.
     *
     * @return string (X)HTML.
     */
    private function _renderInfo()
    {
        return '<h1>Twocents</h1>'
            . $this->_renderIcon()
            . '<p>Version: ' . TWOCENTS_VERSION . '</p>'
            . $this->_renderCopyright() . $this->_renderLicense();
    }

    /**
     * Renders the plugin icon.
     *
     * @return string (X)HTML.
     *
     * @global array The paths of system files and folders.
     * @global array The localization of the plugins.
     */
    private function _renderIcon()
    {
        global $pth, $plugin_tx;

        return tag(
            'img src="' . $pth['folder']['plugins']
            . 'twocents/twocents.png" class="twocents_icon"'
            . ' alt="' . $plugin_tx['twocents']['alt_icon'] . '"'
        );
    }

    /**
     * Renders the copyright info.
     *
     * @return string (X)HTML.
     */
    private function _renderCopyright()
    {
        return <<<EOT
<p>Copyright &copy; 2014
    <a href="http://3-magi.net/" target="_blank">Christoph M. Becker</a>
</p>
EOT;
    }

    /**
     * Renders the license info.
     *
     * @return string (X)HTML.
     */
    private function _renderLicense()
    {
        return <<<EOT
<p class="twocents_license">This program is free software: you can redistribute
it and/or modify it under the terms of the GNU General Public License as
published by the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.</p>
<p class="twocents_license">This program is distributed in the hope that it will
be useful, but <em>without any warranty</em>; without even the implied warranty
of <em>merchantability</em> or <em>fitness for a particular purpose</em>. See
the GNU General Public License for more details.</p>
<p class="twocents_license">You should have received a copy of the GNU General
Public License along with this program. If not, see <a
href="http://www.gnu.org/licenses/"
target="_blank">http://www.gnu.org/licenses/</a>. </p>
EOT;
    }

    /**
     * Renders the comments on a certain topic.
     *
     * @param string $topicname A topicname.
     *
     * @return string (X)HTML.
     *
     * @global XH_CSRFProtection The CSRF protector.
     */
    public function renderComments($topicname)
    {
        global $_XH_csrfProtection;

        $action = isset($_POST['twocents_action'])
            ? stsl($_POST['twocents_action']) : '';
        $html = '';
        switch ($action) {
        case 'add_comment':
            $html .= $this->_addComment($topicname);
            break;
        case 'update_comment':
            if (XH_ADM) {
                $_XH_csrfProtection->check();
                $html .= $this->_updateComment($topicname);
            }
            break;
        case 'remove_comment':
            if (XH_ADM) {
                $_XH_csrfProtection->check();
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
     * @global array             The localization of the plugins.
     * @global XH_CSRFProtection The CSRF protector.
     */
    private function _renderDeleteForm(Twocents_Comment $comment)
    {
        global $plugin_tx, $_XH_csrfProtection;

        return '<form method="post" action="' . XH_hsc($this->_getUrl()) . '">'
            . $_XH_csrfProtection->tokenInput()
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
     * @global array             The localization of the plugins.
     * @global XH_CSRFProtection The CSRF protector.
     */
    private function _renderCommentForm(Twocents_Comment $comment = null)
    {
        global $plugin_tx, $_XH_csrfProtection;

        if (!isset($comment)) {
            $comment = Twocents_Comment::make(null, null);
        }
        $html = '<form class="twocents_form" method="post" action="'
            . XH_hsc($this->_getUrl()) . '">';
        if ($comment->getId()) {
            $html .= $_XH_csrfProtection->tokenInput();
        }
        $html .= tag(
            'input type="hidden" name="twocents_id" value="'
            . XH_hsc($comment->getId()) . '"'
        );
        $html .= '<label><span>' . $plugin_tx['twocents']['label_user'] . '</span>'
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
        return $html;
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

        $ptx = $plugin_tx['twocents'];
        $action = $comment->getId() ? 'update' : 'add';
        $html = '<button name="twocents_action" value="' . $action . '_comment">'
            . $ptx['label_' . $action] . '</button>';
        if ($comment->getId()) {
            $html .= '<a href="' . $this->_getUrl() . '">'
                . $ptx['label_cancel'] . '</a>';
        }
        $html .= '<button type="reset">' . $ptx['label_reset'] . '</button>';
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
