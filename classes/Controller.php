<?php

/**
 * Copyright 2014-2017 Christoph M. Becker
 *
 * This file is part of Twocents_XH.
 *
 * Twocents_XH is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Twocents_XH is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Twocents_XH.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace Twocents;

use HTMLPurifier;
use HTMLPurifier_Config;

class Controller
{
    /**
     * @var Comment
     */
    protected $comment;

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
     * @return string
     */
    protected function renderInfo()
    {
        return '<h1>Twocents</h1>'
            . $this->renderIcon()
            . '<p>Version: ' . TWOCENTS_VERSION . '</p>'
            . $this->renderCopyright() . $this->renderLicense();
    }

    /**
     * @return string
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
     * @return string
     */
    protected function renderCopyright()
    {
        return <<<EOT
<p>Copyright &copy; 2014-2017
    <a href="http://3-magi.net/" target="_blank">Christoph M. Becker</a>
</p>
EOT;
    }

    /**
     * @return string
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
     * @param string $to A markup format ('html' or 'plain').
     * @return string
     */
    protected function convertCommentsTo($to)
    {
        global $plugin_tx;

        $topics = Topic::findAll();
        foreach ($topics as $topic) {
            $comments = Comment::findByTopicname($topic->getName());
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
     * @return string
     */
    protected function importComments()
    {
        global $plugin_cf, $plugin_tx;

        $topics = CommentsTopic::findAll();
        foreach ($topics as $topic) {
            $comments = CommentsComment::findByTopicname($topic->getName());
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
     * @return string
     * @todo Implement!
     */
    protected function importGbook()
    {
        global $plugin_tx;

        return XH_message('info', $plugin_tx['twocents']['message_nyi'])
            . $this->renderMainAdministration();
    }

    /**
     * @return string
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
     * @param string $name
     * @return string
     */
    protected function renderMainAdminButton($name)
    {
        global $plugin_tx;

        return '<p><button type="submit" name="action" value="' . $name . '">'
            . $plugin_tx['twocents']['label_' . $name] . '</button></p>';
    }

    /**
     * @param string $topicname
     * @return string
     */
    public function renderComments($topicname)
    {
        global $sn, $su, $plugin_cf, $plugin_tx, $_XH_csrfProtection;

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
            $this->comment = Comment::find(stsl($_GET['twocents_id']), $topicname);
        }
        $comments = Comment::findByTopicname($topicname, true);
        if ($plugin_cf['twocents']['comments_order'] == 'DESC') {
            $comments = array_reverse($comments);
        }
        $count = count($comments);
        $itemsPerPage = $plugin_cf['twocents']['pagination_max'];
        $pageCount = (int) ceil($count / $itemsPerPage);
        $currentPage = filter_input(
            INPUT_GET,
            'twocents_page',
            FILTER_VALIDATE_INT,
            array('options' => array('min_range' => 1, 'max_range' => $pageCount, 'default' => 1))
        );
        $comments = array_splice($comments, ($currentPage - 1) * $itemsPerPage, $itemsPerPage);
        $pagination = new Pagination($count, $currentPage, $pageCount, $this->getPaginationUrl());
        $view = CommentsView::make($comments, $this->comment, $html);
        $paginationHtml = $pagination->render();
        $html = $paginationHtml . $view->render() . $paginationHtml;
        if (!$this->isXmlHttpRequest()) {
            return "<div>$html</div>";
        } else {
            while (ob_get_level()) {
                ob_end_clean();
            }
            echo $html;
            exit;
        }
    }

    /**
     * @param string $topicname
     * @return bool
     */
    protected function isValidTopicname($topicname)
    {
        return (bool) preg_match('/^[a-z0-9-]+$/i', $topicname);
    }

    /**
     * @param string $topicname
     * @return string
     */
    protected function addComment($topicname)
    {
        global $plugin_cf, $plugin_tx;

        $this->comment = Comment::make($topicname, time());
        $this->comment->setUser(trim(stsl($_POST['twocents_user'])));
        $this->comment->setEmail(trim(stsl($_POST['twocents_email'])));
        $message = trim(stsl($_POST['twocents_message']));
        if (!XH_ADM && $plugin_cf['twocents']['comments_markup'] == 'HTML') {
            $message = $this->purify($message);
        }
        $this->comment->setMessage($message);
        if ($this->isModerated() || ($isSpam = !XH_ADM && $this->isSpam($message))) {
            $this->comment->hide();
        }
        $marker = '<div id="twocents_scroll_marker" class="twocents_scroll_marker">'
            . '</div>';
        $html = $this->renderErrorMessages();
        if (!$html) {
            $this->comment->insert();
            $this->sendNotificationEmail();
            $this->comment = null;
            if ($this->isModerated() || $isSpam) {
                $html .= XH_message('info', $plugin_tx['twocents']['message_moderated']);
            } else {
                $html .= XH_message('success', $plugin_tx['twocents']['message_added']);
            }
            $html .= $marker;
        } else {
            $html = $marker . $html;
        }
        return $html;
    }

    /**
     * @param string $message
     * @return string
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
        $config->set('HTML.Allowed', 'p,blockquote,br,b,strong,i,em,a[href]');
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
     * @param string $text
     * @return string
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
     * @param string $html
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
            ENT_QUOTES,
            'UTF-8'
        );
    }

    /**
     * @return bool
     */
    protected function isModerated()
    {
        global $plugin_cf;

        return $plugin_cf['twocents']['comments_moderated'] && !XH_ADM;
    }

    /**
     * @param string $message
     * @return bool
     */
    private function isSpam($message)
    {
        global $plugin_tx;

        $words = array_map(
            function ($word) {
                return preg_quote(trim($word), '/');
            },
            explode(',', $plugin_tx['twocents']['spam_words'])
        );
        $pattern = implode('|', $words);
        return preg_match("/$pattern/ui", $message);
    }

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
            $mailer = Mailer::make(
                ($plugin_cf['twocents']['email_linebreak'] == 'LF') ? "\n" : "\r\n"
            );
            $mailer->send($email, $ptx['email_subject'], $message, 'From: ' . $email);
        }
    }

    /**
     * @return string
     */
    protected function getUrl()
    {
        return CMSIMPLE_URL . '?' . $_SERVER['QUERY_STRING'];
    }

    /**
     * @return string
     */
    private function getPaginationUrl()
    {
        global $sn;

        $params = $_GET;
        $params['twocents_page'] = '%d';
        $params = array_map(
            function ($key, $value) {
                $param = urlencode($key);
                if ($value !== '') {
                    $param .= '=';
                    if ($key === 'twocents_page') {
                        $param .= $value;
                    } else {
                        $param .= urlencode($value);
                    }
                }
                return $param;
            },
            array_keys($params),
            array_values($params)
        );
        $url = $sn;
        if (!empty($params)) {
            $url .= '?' . implode('&', $params);
        }
        return $url;
    }

    /**
     * @param string $topicname
     * @return string
     */
    protected function updateComment($topicname)
    {
        $this->comment = Comment::find(stsl($_POST['twocents_id']), $topicname);
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
     * @param string $topicname
     */
    protected function toggleVisibility($topicname)
    {
        $comment = Comment::find(stsl($_POST['twocents_id']), $topicname);
        if ($comment->isVisible()) {
            $comment->hide();
        } else {
            $comment->show();
        }
        $comment->update();
    }

    /**
     * @param string $topicname
     */
    protected function deleteComment($topicname)
    {
        $comment = Comment::find(stsl($_POST['twocents_id']), $topicname);
        if (isset($comment)) {
            $comment->delete();
        }
    }

    /**
     * @return string
     */
    protected function renderErrorMessages()
    {
        global $plugin_tx;

        $html = '';
        if (utf8_strlen($this->comment->getUser()) < 2) {
            $html .= XH_message('fail', $plugin_tx['twocents']['error_user']);
        }
        $mailer = Mailer::make();
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
     * @return string
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

    /**
     * @return bool
     */
    private function isXmlHttpRequest()
    {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH'])
            && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest';
    }
}
