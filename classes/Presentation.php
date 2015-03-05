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
                XH_registerStandardPluginMenuItems(true);
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

        $o .= print_plugin_admin('on');
        switch ($admin) {
        case '':
            $o .= $this->_renderInfo();
            break;
        case 'plugin_main':
            $o .= $this->_handleMainAdministration();
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
     * Handles the main administration.
     *
     * @return void
     *
     * @global string            The value of the <var>action</var> GP parameter.
     * @global string            The (X)HTML for the contents area.
     * @global XH_CSRFProtection The CSRF protector.
     */
    private function _handleMainAdministration()
    {
        global $action, $o, $_XH_csrfProtection;

        $o .= '<h1>Twocents &ndash; Conversion</h1>';
        switch ($action) {
        case 'convert_html':
            $_XH_csrfProtection->check();
            $o .= $this->_convertCommentsTo('html');
            break;
        case 'convert_plain':
            $_XH_csrfProtection->check();
            $o .= $this->_convertCommentsTo('plain');
            break;
        case 'import_comments':
            $_XH_csrfProtection->check();
            $o .= $this->_importComments();
            break;
        case 'import_gbook':
            $_XH_csrfProtection->check();
            $o .= $this->_importGbook();
            break;
        default:
            $o .= $this->_renderMainAdministration();
        }
    }

    /**
     * Converts all comments to another markup format.
     *
     * @param string $to A markup format ('html' or 'plain').
     *
     * @return string (X)HTML.
     *
     * @global array The localization of the plugins.
     */
    private function _convertCommentsTo($to)
    {
        global $plugin_tx;

        $topics = Twocents_Topic::findAll();
        foreach ($topics as $topic) {
            $comments = Twocents_Comment::findByTopicname($topic->getName());
            foreach ($comments as $comment) {
                if ($to == 'html') {
                    $message = $this->_htmlify(XH_hsc($comment->getMessage()));
                } else {
                    $message = $this->plainify($comment->getMessage());
                }
                $comment->setMessage($message);
                $comment->update();
            }
        }
        $message = $plugin_tx['twocents']['message_converted_' . $to];
        return  XH_message('success', $message)
            . $this->_renderMainAdministration();
    }

    /**
     * Imports all comments from the Comments plugin.
     *
     * @return string (X)HTML.
     *
     * @global array The configuration of the plugins.
     * @global array The localization of the plugins.
     */
    private function _importComments()
    {
        global $plugin_cf, $plugin_tx;

        $topics = Twocents_CommentsTopic::findAll();
        foreach ($topics as $topic) {
            $comments = Twocents_CommentsComment::findByTopicname($topic->getName());
            foreach ($comments as $comment) {
                $message = $comment->getMessage();
                if ($plugin_cf['twocents']['comments_markup'] == 'HTML') {
                    $message = $this->_purify($message);
                } else {
                    $message = $this->plainify($message);
                }
                $comment->setMessage($message);
                $comment->insert();
            }
        }
        $message = $plugin_tx['twocents']['message_imported_comments'];
        return XH_message('success', $message)
            . $this->_renderMainAdministration();
    }

    /**
     * Imports all comments from the GBook plugin.
     *
     * @return string (X)HTML.
     *
     * @global array The localization of the plugins.
     *
     * @todo Implement!
     */
    private function _importGbook()
    {
        global $plugin_tx;

        return XH_message('info', $plugin_tx['twocents']['message_nyi'])
            . $this->_renderMainAdministration();
    }

    /**
     * Renders the main administration view.
     *
     * @return string (X)HTML.
     *
     * @global string            The script name.
     * @global array             The plugin configuration.
     * @global XH_CSRFProtection The CSRF protector.
     */
    private function _renderMainAdministration()
    {
        global $sn, $plugin_cf, $_XH_csrfProtection;

        $html = '<form action="' . $sn . '?&twocents" method="post">'
            . tag('input type="hidden" name="admin" value="plugin_main"')
            . $_XH_csrfProtection->tokenInput();
        if ($plugin_cf['twocents']['comments_markup'] == 'HTML') {
            $html .= $this->_renderMainAdminButton('convert_plain');
        } else {
            $html .= $this->_renderMainAdminButton('convert_html');
        }
        $html .= $this->_renderMainAdminButton('import_comments')
            . $this->_renderMainAdminButton('import_gbook')
            . '</form>';
        return $html;
    }

    /**
     * Renders a button of the main administration.
     *
     * @param string $name A feature name.
     *
     * @return string (X)HTML.
     */
    private function _renderMainAdminButton($name)
    {
        global $plugin_tx;

        return '<p><button type="submit" name="action" value="' . $name . '">'
            . $plugin_tx['twocents']['label_' . $name] . '</button></p>';
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
        $view = Twocents_CommentsView::make($comments, $this->_comment, $html);
        if (!isset($_POST['twocents_ajax'])) {
            return '<div>' . $view->render() . '</div>';
        } else {
            echo $view->render();
            exit;
        }
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
     *
     * @global array The configuration of the plugins.
     * @global array The localization of the core.
     */
    private function _addComment($topicname)
    {
        global $plugin_cf, $plugin_tx;

        $this->_comment = Twocents_Comment::make(
            $topicname, time()
        );
        $this->_comment->setUser(trim(stsl($_POST['twocents_user'])));
        $this->_comment->setEmail(trim(stsl($_POST['twocents_email'])));
        $message = trim(stsl($_POST['twocents_message']));
        if (!XH_ADM && $plugin_cf['twocents']['comments_markup'] == 'HTML') {
            $message = $this->_purify($message);
        }
        $this->_comment->setMessage($message);
        if ($this->_isModerated()) {
            $this->_comment->hide();
        }
        $marker = '<div id="twocents_scroll_marker" class="twocents_scroll_marker">'
            . '</div>';
        $html = $this->_renderErrorMessages();
        if (!$html) {
            $this->_comment->insert();
            $this->_sendNotificationEmail();
            $this->_comment = null;
            if ($this->_isModerated()) {
                $html .= XH_message(
                    'info', $plugin_tx['twocents']['message_moderated']
                );
            } else {
                $html .= XH_message(
                    'success', $plugin_tx['twocents']['message_added']
                );
            }
            $html .= $marker;
        } else {
            $html = $marker . $html;
        }
        return $html;
    }

    /**
     * Purifies a message.
     *
     * @param string $message A message.
     *
     * @return string
     *
     * @global array The paths of system files and folders.
     * @global array The configuration of the core.
     */
    private function _purify($message)
    {
        global $pth, $cf;

        include_once $pth['folder']['plugins']
            . 'twocents/htmlpurifier/HTMLPurifier.standalone.php';
        $config = HTMLPurifier_Config::createDefault();
        if (!$cf['xhtml']['endtags']) {
            $config->set('HTML.Doctype', 'HTML 4.01 Transitional');
        }
        $config->set('HTML.Allowed', 'p,blockquote,br,b,i,a[href]');
        $config->set('AutoFormat.AutoParagraph', true);
        $config->set('AutoFormat.RemoveEmpty', true);
        $config->set('AutoFormat.RemoveEmpty.RemoveNbsp', true);
        $config->set('HTML.Nofollow', true);
        $config->set('Output.TidyFormat', true);
        $config->set('Output.Newline', "\n");
        $purifier = new HTMLPurifier($config);
        $message = str_replace(array('&nbsp;', "\C2\A0"), ' ', $message);
        return $purifier->purify($message);
    }

    /**
     * Returns a HTMLified text.
     *
     * @param string $text A text.
     *
     * @return string (X)HTML.
     */
    private function _htmlify($text)
    {
        return preg_replace(
            array('/(?:\r\n|\r)/', '/\n{2,}/', '/\n/'),
            array("\n", '</p><p>', tag('br')),
            '<p>' . $text . '</p>'
        );
    }

    /**
     * Returns plainified HTML.
     *
     * @param string $html (X)HTML.
     *
     * @return string
     */
    protected function plainify($html)
    {
        return html_entity_decode(
            strip_tags(
                str_replace(
                    array('</p><p>', tag('br')),
                    array(PHP_EOL . PHP_EOL, PHP_EOL),
                    $html
                )
            ),
            ENT_QUOTES, 'UTF-8'
        );
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
            $message = $this->_comment->getMessage();
            if ($plugin_cf['twocents']['comments_markup'] == 'HTML') {
                $message = strip_tags($message);
            }
            $message = '<' . $this->_getUrl() . '#twocents_comment_'
                . $this->_comment->getId() . '>' . PHP_EOL . PHP_EOL
                . $ptx['label_user'] . ': ' . $this->_comment->getUser() . PHP_EOL
                . $ptx['label_email'] . ': <' . $this->_comment->getEmail()
                . '>' . PHP_EOL
                . $ptx['label_message'] . ':' . PHP_EOL . PHP_EOL
                . $message . PHP_EOL;
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
    private $_comments;

    /**
     * The current comment, if any.
     *
     * @var Twocents_Comment
     */
    private $_currentComment;

    /**
     * (X)HTML messages.
     *
     * @var string
     */
    private $_messages;

    /**
     * Initializes a new instance.
     *
     * @param array            $comments       An array of comments.
     * @param Twocents_Comment $currentComment The current comment.
     * @param string           $messages       (X)HTML messages.
     *
     * @return void
     */
    private function __construct(
        $comments, Twocents_Comment $currentComment = null, $messages = ''
    ) {
        $this->_comments = (array) $comments;
        $this->_currentComment = $currentComment;
        $this->_messages = (string) $messages;
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
                if (isset($this->_currentComment)
                    && $this->_currentComment->getId() == $comment->getId()
                ) {
                    $html .= $this->_messages;
                }
                $view = new Twocents_CommentView($comment, $this->_currentComment);
                $html .= $view->render();
            }
        }
        $html .= '</div>';
        if (!isset($this->_currentComment)
            || $this->_currentComment->getId() == null
        ) {
            $view = new Twocents_CommentFormView($this->_currentComment);
            $html .= $this->_messages . $view->render();
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
    private function _writeScriptsToBjs()
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
            . '<button type="submit" name="twocents_action"'
            . ' value="toggle_visibility">' . $hideLabel . '</button>'
            . '<button type="submit" name="twocents_action" value="remove_comment">'
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
     *
     * @global array The configuration of the plugins.
     */
    private function _renderMessage()
    {
        global $plugin_cf;

        if ($plugin_cf['twocents']['comments_markup'] == 'HTML') {
            return $this->_comment->getMessage();
        } else {
            return preg_replace(
                '/(?:\r\n|\r|\n)/', tag('br'), XH_hsc($this->_comment->getMessage())
            );
        }
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
            . '<button type="submit" name="twocents_action" value="' . $action
            . '_comment">' . $ptx['label_' . $action] . '</button>';
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
