<?php

/**
 * The controllers.
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
    protected $comment;

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
                $this->handleAdministration();
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
    protected function handleAdministration()
    {
        global $admin, $action, $o;

        $o .= print_plugin_admin('on');
        switch ($admin) {
        case '':
            $o .= $this->renderInfo();
            break;
        case 'plugin_main':
            $o .= $this->handleMainAdministration();
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
    protected function renderInfo()
    {
        return '<h1>Twocents</h1>'
            . $this->renderIcon()
            . '<p>Version: ' . TWOCENTS_VERSION . '</p>'
            . $this->renderCopyright() . $this->renderLicense();
    }

    /**
     * Renders the plugin icon.
     *
     * @return string (X)HTML.
     *
     * @global array The paths of system files and folders.
     * @global array The localization of the plugins.
     */
    protected function renderIcon()
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
    protected function renderCopyright()
    {
        return <<<EOT
<p>Copyright &copy; 2014-2015
    <a href="http://3-magi.net/" target="_blank">Christoph M. Becker</a>
</p>
EOT;
    }

    /**
     * Renders the license info.
     *
     * @return string (X)HTML.
     */
    protected function renderLicense()
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
    protected function handleMainAdministration()
    {
        global $action, $o, $_XH_csrfProtection;

        $o .= '<h1>Twocents &ndash; Conversion</h1>';
        switch ($action) {
        case 'convert_html':
            $_XH_csrfProtection->check();
            $o .= $this->convertCommentsTo('html');
            break;
        case 'convert_plain':
            $_XH_csrfProtection->check();
            $o .= $this->convertCommentsTo('plain');
            break;
        case 'import_comments':
            $_XH_csrfProtection->check();
            $o .= $this->importComments();
            break;
        case 'import_gbook':
            $_XH_csrfProtection->check();
            $o .= $this->importGbook();
            break;
        default:
            $o .= $this->renderMainAdministration();
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
    protected function convertCommentsTo($to)
    {
        global $plugin_tx;

        $topics = Twocents_Topic::findAll();
        foreach ($topics as $topic) {
            $comments = Twocents_Comment::findByTopicname($topic->getName());
            foreach ($comments as $comment) {
                if ($to == 'html') {
                    $message = $this->htmlify(XH_hsc($comment->getMessage()));
                } else {
                    $message = $this->plainify($comment->getMessage());
                }
                $comment->setMessage($message);
                $comment->update();
            }
        }
        $message = $plugin_tx['twocents']['message_converted_' . $to];
        return  XH_message('success', $message)
            . $this->renderMainAdministration();
    }

    /**
     * Imports all comments from the Comments plugin.
     *
     * @return string (X)HTML.
     *
     * @global array The configuration of the plugins.
     * @global array The localization of the plugins.
     */
    protected function importComments()
    {
        global $plugin_cf, $plugin_tx;

        $topics = Twocents_CommentsTopic::findAll();
        foreach ($topics as $topic) {
            $comments = Twocents_CommentsComment::findByTopicname($topic->getName());
            foreach ($comments as $comment) {
                $message = $comment->getMessage();
                if ($plugin_cf['twocents']['comments_markup'] == 'HTML') {
                    $message = $this->purify($message);
                } else {
                    $message = $this->plainify($message);
                }
                $comment->setMessage($message);
                $comment->insert();
            }
        }
        $message = $plugin_tx['twocents']['message_imported_comments'];
        return XH_message('success', $message)
            . $this->renderMainAdministration();
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
    protected function importGbook()
    {
        global $plugin_tx;

        return XH_message('info', $plugin_tx['twocents']['message_nyi'])
            . $this->renderMainAdministration();
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
    protected function renderMainAdministration()
    {
        global $sn, $plugin_cf, $_XH_csrfProtection;

        $html = '<form action="' . $sn . '?&twocents" method="post">'
            . tag('input type="hidden" name="admin" value="plugin_main"')
            . $_XH_csrfProtection->tokenInput();
        if ($plugin_cf['twocents']['comments_markup'] == 'HTML') {
            $html .= $this->renderMainAdminButton('convert_plain');
        } else {
            $html .= $this->renderMainAdminButton('convert_html');
        }
        $html .= $this->renderMainAdminButton('import_comments')
            . $this->renderMainAdminButton('import_gbook')
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
    protected function renderMainAdminButton($name)
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

        if (!$this->isValidTopicname($topicname)) {
            return XH_message('fail', $plugin_tx['twocents']['error_topicname']);
        }
        $action = isset($_POST['twocents_action'])
            ? stsl($_POST['twocents_action']) : '';
        $html = '';
        switch ($action) {
        case 'add_comment':
            $html .= $this->addComment($topicname);
            break;
        case 'update_comment':
            if (XH_ADM) {
                $_XH_csrfProtection->check();
                $html .= $this->updateComment($topicname);
            }
            break;
        case 'toggle_visibility':
            if (XH_ADM) {
                $_XH_csrfProtection->check();
                $this->toggleVisibility($topicname);
            }
            break;
        case 'remove_comment':
            if (XH_ADM) {
                $_XH_csrfProtection->check();
                $this->deleteComment($topicname);
            }
            break;
        }
        if (isset($_GET['twocents_id'])) {
            $this->comment = Twocents_Comment::find(
                stsl($_GET['twocents_id']), $topicname
            );
        }
        $comments = Twocents_Comment::findByTopicname($topicname);
        if ($plugin_cf['twocents']['comments_order'] == 'DESC') {
            $comments = array_reverse($comments);
        }
        $view = Twocents_CommentsView::make($comments, $this->comment, $html);
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
    protected function isValidTopicname($topicname)
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
    protected function addComment($topicname)
    {
        global $plugin_cf, $plugin_tx;

        $this->comment = Twocents_Comment::make(
            $topicname, time()
        );
        $this->comment->setUser(trim(stsl($_POST['twocents_user'])));
        $this->comment->setEmail(trim(stsl($_POST['twocents_email'])));
        $message = trim(stsl($_POST['twocents_message']));
        if (!XH_ADM && $plugin_cf['twocents']['comments_markup'] == 'HTML') {
            $message = $this->purify($message);
        }
        $this->comment->setMessage($message);
        if ($this->isModerated()) {
            $this->comment->hide();
        }
        $marker = '<div id="twocents_scroll_marker" class="twocents_scroll_marker">'
            . '</div>';
        $html = $this->renderErrorMessages();
        if (!$html) {
            $this->comment->insert();
            $this->sendNotificationEmail();
            $this->comment = null;
            if ($this->isModerated()) {
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
    protected function purify($message)
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
    protected function htmlify($text)
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
    protected function isModerated()
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
    protected function sendNotificationEmail()
    {
        global $plugin_cf, $plugin_tx;

        $email = $plugin_cf['twocents']['email_address'];
        if (!XH_ADM && $email != '') {
            $ptx = $plugin_tx['twocents'];
            $message = $this->comment->getMessage();
            if ($plugin_cf['twocents']['comments_markup'] == 'HTML') {
                $message = strip_tags($message);
            }
            $message = '<' . $this->getUrl() . '#twocents_comment_'
                . $this->comment->getId() . '>' . PHP_EOL . PHP_EOL
                . $ptx['label_user'] . ': ' . $this->comment->getUser() . PHP_EOL
                . $ptx['label_email'] . ': <' . $this->comment->getEmail()
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
    protected function getUrl()
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
    protected function updateComment($topicname)
    {
        $this->comment = Twocents_Comment::find(
            stsl($_POST['twocents_id']), $topicname
        );
        $this->comment->setUser(trim(stsl($_POST['twocents_user'])));
        $this->comment->setEmail(trim(stsl($_POST['twocents_email'])));
        $this->comment->setMessage(trim(stsl($_POST['twocents_message'])));
        $html = $this->renderErrorMessages();
        if (!$html) {
            $this->comment->update();
            $this->comment = null;
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
    protected function toggleVisibility($topicname)
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
    protected function deleteComment($topicname)
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
    protected function renderErrorMessages()
    {
        global $plugin_tx;

        $html = '';
        if (utf8_strlen($this->comment->getUser()) < 2) {
            $html .= XH_message('fail', $plugin_tx['twocents']['error_user']);
        }
        $mailer = Twocents_Mailer::make();
        if (!$mailer->isValidAddress($this->comment->getEmail())) {
            $html .= XH_message('fail', $plugin_tx['twocents']['error_email']);
        }
        if (utf8_strlen($this->comment->getMessage()) < 2) {
            $html .= XH_message('fail', $plugin_tx['twocents']['error_message']);
        }
        $html .= $this->renderCaptchaError();
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
    protected function renderCaptchaError()
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

?>
