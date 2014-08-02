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
     * @global array             The configuration of the plugins.
     * @global array             The localization of the plugins.
     * @global XH_CSRFProtection The CSRF protector.
     */
    public function renderComments($topicname)
    {
        global $plugin_cf, $plugin_tx, $_XH_csrfProtection;

        if (!$this->_isValidTopicname($topicname)) {
            return XH_message('fail', $plugin_tx['twocents']['error_topicname']);
        }
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
        case 'toggle_visibility':
            if (XH_ADM) {
                $_XH_csrfProtection->check();
                $this->_toggleVisibility($topicname);
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
        if ($plugin_cf['twocents']['comments_order'] == 'DESC') {
            $comments = array_reverse($comments);
        }
        $html .= Twocents_CommentsView::make($comments, $this->_comment)->render();
        return $html;
    }

    /**
     * Returns whether a topicname is valid.
     *
     * @param string $topicname A topicname.
     *
     * @return bool
     */
    private function _isValidTopicname($topicname)
    {
        return (bool) preg_match('/^[a-z0-9-]+$/i', $topicname);
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
        global $plugin_tx;

        $this->_comment = Twocents_Comment::make(
            $topicname, time()
        );
        $this->_comment->setUser(trim(stsl($_POST['twocents_user'])));
        $this->_comment->setEmail(trim(stsl($_POST['twocents_email'])));
        $this->_comment->setMessage(trim(stsl($_POST['twocents_message'])));
        if ($this->_isModerated()) {
            $this->_comment->hide();
        }
        $html = $this->_renderErrorMessages();
        if (!$html) {
            $this->_comment->insert();
            $this->_sendNotificationEmail();
            $this->_comment = null;
            if ($this->_isModerated()) {
                $html = XH_message(
                    'info', $plugin_tx['twocents']['message_moderated']
                );
            }
        }
        return $html;
    }

    /**
     * Returns whether the added comment is moderated.
     *
     * @return bool
     */
    private function _isModerated()
    {
        global $plugin_cf;

        return $plugin_cf['twocents']['comments_moderated'] && !XH_ADM;
    }

    /**
     * Sends an email notification if an address is configured and we're not in
     * admin mode.
     *
     * @return void
     *
     * @global array The configuration of the plugins.
     * @global array The localization of the plugins.
     */
    private function _sendNotificationEmail()
    {
        global $plugin_cf, $plugin_tx;

        $email = $plugin_cf['twocents']['email_address'];
        if (!XH_ADM && $email != '') {
            $ptx = $plugin_tx['twocents'];
            $message = '<' . $this->_getUrl() . '#twocents_comment_'
                . $this->_comment->getId() . '>' . PHP_EOL . PHP_EOL
                . $ptx['label_user'] . ': ' . $this->_comment->getUser() . PHP_EOL
                . $ptx['label_email'] . ': <' . $this->_comment->getEmail()
                . '>' . PHP_EOL
                . $ptx['label_message'] . ':' . PHP_EOL . PHP_EOL
                . $this->_comment->getMessage() . PHP_EOL;
            $mailer = Twocents_Mailer::make(
                ($plugin_cf['twocents']['email_linebreak'] == 'LF') ? "\n" : "\r\n"
            );
            $mailer->send(
                $email, $ptx['email_subject'], $message, 'From: ' . $email
            );
        }
    }

    /**
     * Returns the URL that the new comment was posted to.
     *
     * @return string
     */
    private function _getUrl()
    {
        return CMSIMPLE_URL . '?' . $_SERVER['QUERY_STRING'];
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
     * Toggles the visibility of a comment.
     *
     * @param string $topicname A topicname.
     *
     * @return void
     */
    private function _toggleVisibility($topicname)
    {
        $comment = Twocents_Comment::find(
            stsl($_POST['twocents_id']), $topicname
        );
        if ($comment->isVisible()) {
            $comment->hide();
        } else {
            $comment->show();
        }
        $comment->update();
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
        if (isset($comment)) {
            $comment->delete();
        }
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
        if (utf8_strlen($this->_comment->getUser()) < 2) {
            $html .= XH_message('fail', $plugin_tx['twocents']['error_user']);
        }
        $mailer = Twocents_Mailer::make();
        if (!$mailer->isValidAddress($this->_comment->getEmail())) {
            $html .= XH_message('fail', $plugin_tx['twocents']['error_email']);
        }
        if (utf8_strlen($this->_comment->getMessage()) < 2) {
            $html .= XH_message('fail', $plugin_tx['twocents']['error_message']);
        }
        $html .= $this->_renderCaptchaError();
        return $html;
    }

    /**
     * Renders the CAPTCHA error message, if any.
     *
     * @return string (X)HTML.
     *
     * @global array The paths of system files and folders.
     * @global array The configuration of the plugins.
     * @global array The localization of the plugins.
     */
    private function _renderCaptchaError()
    {
        global $pth, $plugin_cf, $plugin_tx;

        $pluginname = $plugin_cf['twocents']['captcha_plugin'];
        $filename = $pth['folder']['plugins'] . $pluginname . '/captcha.php';
        if (!XH_ADM && $pluginname && is_readable($filename)) {
            include_once $filename;
            if (!call_user_func($pluginname . '_captcha_check')) {
                return XH_message('fail', $plugin_tx['twocents']['error_captcha']);
            }
        }
        return '';
    }
}

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
     */
    public function render()
    {
        $this->_writeScriptsToBjs();
        $html = '<div class="twocents_comments">';
        foreach ($this->_comments as $comment) {
            if ($comment->isVisible() || XH_ADM) {
                $view = new Twocents_CommentView($comment, $this->_currentComment);
                $html .= $view->render();
            }
        }
        $html .= '</div>';
        if (!isset($this->_currentComment)
            || $this->_currentComment->getId() == null
        ) {
            $view = new Twocents_CommentFormView($this->_currentComment);
            $html .= $view->render();
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
     */
    private function _writeScriptsToBjs()
    {
        global $pth, $bjs, $plugin_tx;

        $message = addcslashes($plugin_tx['twocents']['message_delete'], "\"\r\n");
        $bjs .= <<<EOT
<script type="text/javascript">/* <[CDATA[ */
TWOCENTS = {deleteMessage: "$message"};
/* ]]> */</script>
<script type="text/javascript"
        src="{$pth['folder']['plugins']}twocents/twocents.js"></script>
EOT;
    }

}

/**
 * The comment views.
 *
 * @category CMSimple_XH
 * @package  Twocents
 * @author   Christoph M. Becker <cmbecker69@gmx.de>
 * @license  http://www.gnu.org/licenses/gpl-3.0.en.html GNU GPLv3
 * @link     http://3-magi.net/?CMSimple_XH/Twocents_XH
 */
class Twocents_CommentView
{
    /**
     * The comment to render.
     *
     * @var Twocents_Comment
     */
    private $_comment;

    /**
     * The current comment, if any.
     *
     * @var Twocents_Comment
     */
    private $_currentComment;

    /**
     * Initializes a new instance.
     *
     * @param Twocents_Comment $comment        A comment to render.
     * @param Twocents_Comment $currentComment The posted comment.
     *
     * @return void
     */
    public function __construct(
        Twocents_Comment $comment, Twocents_Comment $currentComment = null
    ) {
        $this->_comment = $comment;
        $this->_currentComment = $currentComment;
    }

    /**
     * Renders a certain comment.
     *
     * @return string (X)HTML.
     */
    public function render()
    {
        $id = $this->_isCurrentComment()
            ? ''
            : ' id="twocents_comment_' . $this->_comment->getId() . '"';
        $class = $this->_comment->isVisible() ? '' : ' twocents_hidden';
        $html = '<div' . $id . ' class="twocents_comment' . $class . '">';
        if ($this->_isCurrentComment()) {
            $view = new Twocents_CommentFormView($this->_currentComment);
            $html .= $view->render();
        } else {
            if (XH_ADM) {
                $html .= $this->_renderAdminTools();
            }
            $html .= '<p>' . $this->_renderHeading() . '</p>'
                . '<blockquote>' . $this->_renderMessage()
                . '</blockquote>';
        }
        $html .= '</div>';
        return $html;
    }

    /**
     * Renders the admin tools.
     *
     * @return string (X)HTML.
     */
    private function _renderAdminTools()
    {
        return '<div class="twocents_admin_tools">'
            . $this->_renderEditLink()
            . $this->_renderDeleteForm()
            . '</div>';
    }

    /**
     * Renders the delete form.
     *
     * @return string (X)HTML.
     *
     * @global array             The localization of the plugins.
     * @global XH_CSRFProtection The CSRF protector.
     */
    private function _renderDeleteForm()
    {
        global $plugin_tx, $_XH_csrfProtection;

        $hideLabel = $this->_comment->isVisible()
            ? $plugin_tx['twocents']['label_hide']
            : $plugin_tx['twocents']['label_show'];
        return '<form method="post" action="' . XH_hsc($this->_getUrl()) . '">'
            . $_XH_csrfProtection->tokenInput()
            . tag(
                'input type="hidden" name="twocents_id" value="'
                . $this->_comment->getId() . '"'
            )
            . '<button name="twocents_action" value="toggle_visibility">'
            . $hideLabel . '</button>'
            . '<button name="twocents_action" value="remove_comment">'
            . $plugin_tx['twocents']['label_delete'] . '</button>'
            . '</form>';
    }

    /**
     * Renders the edit link.
     *
     * @return string (X)HTML.
     *
     * @global array The localization of the plugins.
     */
    private function _renderEditLink()
    {
        global $plugin_tx;

        $url = $this->_getUrl() . '&twocents_id=' . $this->_comment->getId();
        return '<a href="' . XH_hsc($url) . '">'
            . $plugin_tx['twocents']['label_edit'] . '</a>';
    }

    /**
     * Renders the comment heading.
     *
     * @return string (X)HTML.
     *
     * @global array The localization of the plugins.
     */
    private function _renderHeading()
    {
        global $plugin_tx;

        $date = date(
            $plugin_tx['twocents']['format_date'], $this->_comment->getTime()
        );
        $time = date(
            $plugin_tx['twocents']['format_time'], $this->_comment->getTime()
        );
        return strtr(
            $plugin_tx['twocents']['format_heading'],
            array(
                '{DATE}' => $date,
                '{TIME}' => $time,
                '{USER}' => XH_hsc($this->_comment->getUser())
            )
        );
    }

    /**
     * Renders the comment message.
     *
     * @return string (X)HTML.
     */
    private function _renderMessage()
    {
        return preg_replace(
            '/(?:\r\n|\r|\n)/', tag('br'), XH_hsc($this->_comment->getMessage())
        );
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

    /**
     * Returns whether a comment is the current comment.
     *
     * @return bool
     */
    private function _isCurrentComment()
    {
        return isset($this->_currentComment)
            && $this->_currentComment->getId() == $this->_comment->getId();
    }
}

/**
 * The comment form views.
 *
 * @category CMSimple_XH
 * @package  Twocents
 * @author   Christoph M. Becker <cmbecker69@gmx.de>
 * @license  http://www.gnu.org/licenses/gpl-3.0.en.html GNU GPLv3
 * @link     http://3-magi.net/?CMSimple_XH/Twocents_XH
 */
class Twocents_CommentFormView
{
    /**
     * The comment.
     *
     * @var Twocents_Comment
     */
    private $_comment;

    /**
     * Initializes a new instance.
     *
     * @param Twocents_Comment $comment A comment.
     *
     * @return void
     */
    public function __construct(Twocents_Comment $comment = null)
    {
        if (isset($comment)) {
            $this->_comment = $comment;
        } else {
            $this->_comment = Twocents_Comment::make(null, null);
        }
    }

    /**
     * Renders the view.
     *
     * @return string (X)HTML.
     *
     * @global array The localization of the plugins.
     */
    public function render()
    {
        global $plugin_tx;

        $url = XH_hsc($this->_getUrl());
        return '<form class="twocents_form" method="post" action="' . $url . '">'
            . $this->_renderHiddenFormFields()
            . $this->_renderUserInput()
            . $this->_renderEmailInput()
            . $this->_renderMessageTextarea()
            . $this->_renderCaptcha()
            . $this->_renderButtons()
            . '</form>';
    }

    /**
     * Renders the hidden form fields.
     *
     * @return string (X)HTML.
     *
     * @global XH_CSRFProtection The CSRF protector.
     */
    private function _renderHiddenFormFields()
    {
        global $_XH_csrfProtection;

        $html = '';
        if ($this->_comment->getId()) {
            $html .= $_XH_csrfProtection->tokenInput();
        }
        $html .= tag(
            'input type="hidden" name="twocents_id" value="'
            . XH_hsc($this->_comment->getId()) . '"'
        );
        return $html;
    }

    /**
     * Renders the user input field.
     *
     * @return string (X)HTML.
     *
     * @global array The localization of the plugins.
     */
    private function _renderUserInput()
    {
        global $plugin_tx;

        return '<div><label><span>' . $plugin_tx['twocents']['label_user']
            . '</span>'
            . tag(
                'input type="text" name="twocents_user" value="'
                . XH_hsc($this->_comment->getUser())
                . '" size="20" required="required"'
            )
            . '</label></div>';
    }

    /**
     * Renders the email input field.
     *
     * @return string (X)HTML.
     *
     * @global array The localization of the plugins.
     */
    private function _renderEmailInput()
    {
        global $plugin_tx;

        return '<div><label><span>' . $plugin_tx['twocents']['label_email']
            . '</span>'
            . tag(
                'input type="email" name="twocents_email" value="'
                . XH_hsc($this->_comment->getEmail())
                . '" size="20" required="required"'
            )
            . '</label></div>';
    }

    /**
     * Renders the message textarea.
     *
     * @return string
     *
     * @global array The localization of the plugins.
     */
    private function _renderMessageTextarea()
    {
        global $plugin_tx;

        return '<div><label><span>' . $plugin_tx['twocents']['label_message']
            . '</span>'
            . '<textarea name="twocents_message" cols="50" rows="8"'
            . ' required="required">'
            . XH_hsc($this->_comment->getMessage()) . '</textarea></label></div>';
    }

    /**
     * Renders the CAPTCHA, if configured and available.
     *
     * @return string (X)HTML.
     *
     * @global array The paths of system files and folders.
     * @global array The configuration of the plugins.
     */
    private function _renderCaptcha()
    {
        global $pth, $plugin_cf;

        $pluginname = $plugin_cf['twocents']['captcha_plugin'];
        $filename = $pth['folder']['plugins'] . $pluginname . '/captcha.php';
        if (!XH_ADM && $pluginname && is_readable($filename)) {
            include_once $filename;
            return call_user_func($pluginname . '_captcha_display');
        } else {
            return '';
        }
    }

    /**
     * Renders the form buttons.
     *
     * @return string (X)HTML.
     *
     * @global array The localization of the plugins.
     */
    private function _renderButtons()
    {
        global $plugin_tx;

        $ptx = $plugin_tx['twocents'];
        $action = $this->_comment->getId() ? 'update' : 'add';
        $html = '<div class="twocents_form_buttons">'
            . '<button name="twocents_action" value="' . $action . '_comment">'
            . $ptx['label_' . $action] . '</button>';
        if ($this->_comment->getId()) {
            $html .= '<a href="' . $this->_getUrl() . '">'
                . $ptx['label_cancel'] . '</a>';
        }
        $html .= '<button type="reset">' . $ptx['label_reset'] . '</button>'
            . '</div>';
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
