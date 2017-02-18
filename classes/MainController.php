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

class MainController extends AbstractController
{
    /**
     * @var string
     */
    private $topicname;

    /**
     * @var Comment
     */
    protected $comment;

    /**
     * @var object
     */
    private $csrfProtector;

    /**
     * @param string $topicname
     */
    public function __construct($topicname)
    {
        global $_XH_csrfProtection;

        $this->topicname = $topicname;
        if (isset($_XH_csrfProtection)) {
            $this->csrfProtector = $_XH_csrfProtection;
        }
    }

    public function toggleVisibilityAction()
    {
        $comment = Comment::find(stsl($_POST['twocents_id']), $this->topicname);
        if ($comment->isVisible()) {
            $comment->hide();
        } else {
            $comment->show();
        }
        $comment->update();
        $this->redirectToDefault();
    }

    public function deleteCommentAction()
    {
        $comment = Comment::find(stsl($_POST['twocents_id']), $this->topicname);
        if (isset($comment)) {
            $comment->delete();
        }
        $this->redirectToDefault();
    }

    public function renderComments()
    {
        global $plugin_cf, $plugin_tx;

        if (!$this->isValidTopicname()) {
            return XH_message('fail', $plugin_tx['twocents']['error_topicname']);
        }
        $action = isset($_POST['twocents_action'])
            ? stsl($_POST['twocents_action']) : '';
        $html = '';
        switch ($action) {
            case 'add_comment':
                $html .= $this->addComment();
                break;
            case 'update_comment':
                if (XH_ADM) {
                    $this->csrfProtector->check();
                    $html .= $this->updateComment();
                }
                break;
            case 'toggle_visibility':
                if (XH_ADM) {
                    $this->csrfProtector->check();
                    $this->toggleVisibilityAction();
                }
                break;
            case 'remove_comment':
                if (XH_ADM) {
                    $this->csrfProtector->check();
                    $this->deleteCommentAction();
                }
                break;
        }
        if (isset($_GET['twocents_id'])) {
            $this->comment = Comment::find(stsl($_GET['twocents_id']), $this->topicname);
        }
        $comments = Comment::findByTopicname($this->topicname, true);
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
     * @return bool
     */
    protected function isValidTopicname()
    {
        return (bool) preg_match('/^[a-z0-9-]+$/i', $this->topicname);
    }

    /**
     * @return string
     */
    protected function addComment()
    {
        global $plugin_cf, $plugin_tx;

        $this->comment = Comment::make($this->topicname, time());
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
            $message = '<' . $this->getEmailUrl() . '#twocents_comment_'
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
    protected function getEmailUrl()
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
     * @return string
     */
    protected function updateComment()
    {
        $this->comment = Comment::find(stsl($_POST['twocents_id']), $this->topicname);
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

    private function redirectToDefault()
    {
        $url = CMSIMPLE_URL . '?' . $_SERVER['QUERY_STRING'];
        header("Location: $url", true, 303);
        exit;
    }
}
