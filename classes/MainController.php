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

use DomainException;
use XH\CSRFProtection as CsrfProtector;

class MainController
{
    /** @var string */
    private $pluginsFolder;

    /** @var array<string,string> */
    private $conf;

    /** @var array<string,string> */
    private $lang;

    /** @var CsrfProtector|null */
    private $csrfProtector;

    /**
     * @var string
     */
    private $topicname;

    /**
     * @var bool
     */
    private $readonly;

    /**
     * @var Comment|null
     */
    private $comment;

    /**
     * @var string
     */
    private $messages;

    /**
     * @param string $pluginsFolder
     * @param array<string,string> $conf
     * @param array<string,string> $lang
     * @param CsrfProtector|null $csrfProtector
     * @param string $topicname
     * @param bool $readonly
     * @throws DomainException
     */
    public function __construct($pluginsFolder, array $conf, array $lang, $csrfProtector, $topicname, $readonly)
    {
        $this->pluginsFolder = $pluginsFolder;
        $this->conf = $conf;
        $this->lang = $lang;
        $this->csrfProtector = $csrfProtector;
        if (!$this->isValidTopicname($topicname)) {
            throw new DomainException;
        }
        $this->topicname = $topicname;
        $this->readonly = $readonly;
        $this->messages = '';
    }

    public function toggleVisibilityAction()
    {
        if (!(defined('XH_ADM') && XH_ADM)) {
            return;
        }
        $this->csrfProtector->check();
        $comment = Comment::find($_POST['twocents_id'], $this->topicname);
        if ($comment->isVisible()) {
            $comment->hide();
        } else {
            $comment->show();
        }
        $comment->update();
        $this->redirectToDefault();
    }

    public function removeCommentAction()
    {
        if (!(defined('XH_ADM') && XH_ADM)) {
            return;
        }
        $this->csrfProtector->check();
        $comment = Comment::find($_POST['twocents_id'], $this->topicname);
        if (isset($comment)) {
            $comment->delete();
        }
        $this->redirectToDefault();
    }

    public function defaultAction()
    {
        if (isset($_GET['twocents_id'])) {
            $this->comment = Comment::find($_GET['twocents_id'], $this->topicname);
        }
        $comments = Comment::findByTopicname($this->topicname, true, $this->conf['comments_order'] === 'ASC');
        $count = count($comments);
        $itemsPerPage = (int) $this->conf['pagination_max'];
        $pageCount = (int) ceil($count / $itemsPerPage);
        $currentPage = isset($_GET['twocents_page']) ? max(1, min($pageCount, $_GET['twocents_page'])) : 1;
        $comments = array_splice($comments, ($currentPage - 1) * $itemsPerPage, $itemsPerPage);
        $pagination = $this->preparePaginationView($count, $currentPage, $pageCount);
        ob_start();
        if (isset($pagination)) {
            echo $pagination;
        }
        echo $this->prepareCommentsView($comments);
        if (isset($pagination)) {
            echo $pagination;
        }
        $html = ob_get_clean();
        if (!$this->isXmlHttpRequest()) {
            echo "<div class=\"twocents_container\">$html</div>";
        } else {
            while (ob_get_level()) {
                ob_end_clean();
            }
            echo $html;
            exit;
        }
    }

    /**
     * @param int $commentCount
     * @param int $page
     * @param int $pageCount
     * @return ?string
     */
    private function preparePaginationView($commentCount, $page, $pageCount)
    {
        if ($pageCount <= 1) {
            return null;
        }
        $pagination = new Pagination($page, $pageCount);
        $url = Url::getCurrent();
        $currentPage = $page;
        $view = new View("{$this->pluginsFolder}twocents/views/", $this->lang);
        return $view->render('pagination', [
            'itemCount' => $commentCount,
            'pages' => array_map(
                function ($page) use ($url, $currentPage) {
                    if (isset($page)) {
                        return (object) array(
                            'index' => $page,
                            'url' => $url->without('twocents_id')->with('twocents_page', $page),
                            'isCurrent' => $page === $currentPage,
                            'isEllipsis' => false
                        );
                    } else {
                        return (object) ['isEllipsis' => true];
                    }
                },
                $pagination->gatherPages()
            )
            ]);
    }

    /**
     * @param Comment[] $comments
     * @return string
     */
    private function prepareCommentsView(array $comments)
    {
        $this->writeScriptsToBjs();
        $mayAddComment = (!isset($this->comment) || $this->comment->getId() == null)
            && ((defined('XH_ADM') && XH_ADM) || !$this->readonly);
        $view = new View("{$this->pluginsFolder}twocents/views/", $this->lang);
        return $view->render('comments', [
            'comments' => array_map(
                function ($comment) {
                    return (object) array(
                        'isCurrent' => $this->isCurrentComment($comment),
                        'view' => $this->prepareCommentView($comment)
                    );
                },
                $comments
            ),
            'hasCommentFormAbove' => $mayAddComment && $this->conf['comments_order'] === 'DESC',
            'hasCommentFormBelow' => $mayAddComment && $this->conf['comments_order'] === 'ASC',
            'commentForm' => $mayAddComment ? $this->prepareCommentForm($this->comment) : null,
            'messages' => new HtmlString($this->messages)
        ]);
    }

    private function writeScriptsToBjs()
    {
        global $bjs;
        static $done = false;

        if ($done) {
            return;
        } else {
            $done = true;
        }
        $config = array();
        foreach (array('comments_markup') as $property) {
            $config[$property] = $this->conf[$property];
        }
        $properties = array(
            'label_new',
            'label_bold',
            'label_italic',
            'label_link',
            'label_unlink',
            'message_delete',
            'message_link'
        );
        foreach ($properties as $property) {
            $config[$property] = $this->lang[$property];
        }
        $filename = "{$this->pluginsFolder}twocents/twocents.min.js";
        if (!file_exists($filename)) {
            $filename = "{$this->pluginsFolder}twocents/twocents.js";
        }
        $view = new View("{$this->pluginsFolder}twocents/views/", $this->lang);
        $bjs .= $view->render('scripts', [
            'json' => new HtmlString(json_encode($config)),
            'filename' => $filename
        ]);
    }

    /**
     * @return string
     */
    private function prepareCommentView(Comment $comment)
    {
        $isCurrentComment = $this->isCurrentComment($comment);
        $data = [
            'id' => 'twocents_comment_' . $comment->getId(),
            'className' => $comment->isVisible() ? '' : ' twocents_hidden',
            'isCurrentComment' => $isCurrentComment
        ];
        if ($isCurrentComment) {
            $data['form'] = $this->prepareCommentForm($this->comment);
        } else {
            $url = Url::getCurrent()->without('twocents_id');
            $data += [
                'isAdmin' => defined('XH_ADM') && XH_ADM,
                'url' => $url,
                'editUrl' => $url->with('twocents_id', $comment->getId()),
                'comment' => $comment,
                'visibility' => $comment->isVisible() ? 'label_hide' : 'label_show',
                'attribution' => new HtmlString($this->renderAttribution($comment)),
                'message' => new HtmlString($this->renderMessage($comment))
            ];
            if (defined('XH_ADM') && XH_ADM) {
                $data['csrfTokenInput'] = new HtmlString($this->csrfProtector->tokenInput());
            }
        }
        $view = new View("{$this->pluginsFolder}twocents/views/", $this->lang);
        return $view->render('comment', $data);
    }

    /**
     * @return string
     */
    private function prepareCommentForm(Comment $comment = null)
    {
        if (!isset($comment)) {
            $comment = Comment::make(null, null);
        }
        $url = Url::getCurrent()->without('twocents_id');
        $data = [
            'action' => $comment->getId() ? 'update' : 'add',
            'comment' => $comment,
            'captcha' => new HtmlString($this->renderCaptcha()),
        ];
        if ($comment->getId()) {
            $data += [
                'url' => $url,
                'csrfTokenInput' => new HtmlString($this->csrfProtector->tokenInput())
            ];
        } else {
            $page = $this->conf['comments_order'] === 'ASC' ? '2147483647' : '0';
            $data += [
                'url' => $url->with('twocents_page', $page),
                'csrfTokenInput' => ''
            ];
        }
        $view = new View("{$this->pluginsFolder}twocents/views/", $this->lang);
        return $view->render('comment-form', $data);
    }

    /**
     * @return string
     */
    private function renderCaptcha()
    {
        $pluginname = $this->conf['captcha_plugin'];
        $filename = "{$this->pluginsFolder}$pluginname/captcha.php";
        if (!(defined('XH_ADM') && XH_ADM) && $pluginname && is_readable($filename)) {
            include_once $filename;
            return call_user_func($pluginname . '_captcha_display');
        } else {
            return '';
        }
    }

    /**
     * @return string
     */
    private function renderAttribution(Comment $comment)
    {
        $date = date($this->lang['format_date'], $comment->getTime());
        $time = date($this->lang['format_time'], $comment->getTime());
        return strtr(
            $this->lang['format_heading'],
            array(
                '{DATE}' => $date,
                '{TIME}' => $time,
                '{USER}' => XH_hsc($comment->getUser())
            )
        );
    }

    /**
     * @return string
     */
    private function renderMessage(Comment $comment)
    {
        if ($this->conf['comments_markup'] == 'HTML') {
            return $comment->getMessage();
        } else {
            return preg_replace('/(?:\r\n|\r|\n)/', tag('br'), XH_hsc($comment->getMessage()));
        }
    }

    /**
     * @return bool
     */
    private function isCurrentComment(Comment $comment)
    {
        return isset($this->comment) && $this->comment->getId() == $comment->getId();
    }

    /**
     * @param string $topicname
     * @return bool
     */
    private function isValidTopicname($topicname)
    {
        return (bool) preg_match('/^[a-z0-9-]+$/i', $topicname);
    }

    public function addCommentAction()
    {
        if (!(defined('XH_ADM') && XH_ADM) && $this->readonly) {
            $this->defaultAction();
        }
        $this->comment = Comment::make($this->topicname, time());
        $this->comment->setUser(trim($_POST['twocents_user']));
        $this->comment->setEmail(trim($_POST['twocents_email']));
        $message = trim($_POST['twocents_message']);
        if (!(defined('XH_ADM') && XH_ADM) && $this->conf['comments_markup'] == 'HTML') {
            $htmlCleaner = new HtmlCleaner("{$this->pluginsFolder}twocents/", false);
            $message = $htmlCleaner->clean($message);
        }
        $this->comment->setMessage($message);
        $isSpam = false;
        $spamFilter = new SpamFilter;
        if ($this->isModerated() || ($isSpam = !(defined('XH_ADM') && XH_ADM) && $spamFilter->isSpam($message))) {
            $this->comment->hide();
        }
        $marker = '<div id="twocents_scroll_marker" class="twocents_scroll_marker">'
            . '</div>';
        if ($this->validateFormSubmission()) {
            $this->comment->insert(uniqid());
            $this->sendNotificationEmail();
            $this->comment = null;
            if ($this->isModerated() || $isSpam) {
                $this->messages .= XH_message('info', $this->lang['message_moderated']);
            } else {
                $this->messages .= XH_message('success', $this->lang['message_added']);
            }
            $this->messages .= $marker;
        } else {
            $this->messages = $marker . $this->messages;
        }
        $this->defaultAction();
    }

    /**
     * @return bool
     */
    private function isModerated()
    {
        return $this->conf['comments_moderated'] && !(defined('XH_ADM') && XH_ADM);
    }

    private function sendNotificationEmail()
    {
        $email = $this->conf['email_address'];
        if (!(defined('XH_ADM') && XH_ADM) && $email !== '') {
            $message = $this->comment->getMessage();
            if ($this->conf['comments_markup'] === 'HTML') {
                $message = strip_tags($message);
            }
            $url = Url::getCurrent()->getAbsolute()
                . "#twocents_comment_" . $this->comment->getId();
            $attribution = sprintf(
                $this->lang['email_attribution'],
                $url,
                $this->comment->getUser(),
                $this->comment->getEmail()
            );
            $body = $attribution. "\n\n> " . str_replace("\n", "\n> ", $message);
            $replyTo = str_replace(["\n", "\r"], '', $this->comment->getEmail());
            $mailer = new Mailer(new MailHelper(), ($this->conf['email_linebreak'] === 'LF') ? "\n" : "\r\n");
            $mailer->send($email, $this->lang['email_subject'], $body, "From: $email\r\nReply-To: $replyTo");
        }
    }

    public function updateCommentAction()
    {
        if (!(defined('XH_ADM') && XH_ADM)) {
            return;
        }
        $this->csrfProtector->check();
        $this->comment = Comment::find($_POST['twocents_id'], $this->topicname);
        $this->comment->setUser(trim($_POST['twocents_user']));
        $this->comment->setEmail(trim($_POST['twocents_email']));
        $this->comment->setMessage(trim($_POST['twocents_message']));
        if ($this->validateFormSubmission()) {
            $this->comment->update();
            $this->comment = null;
        }
        $this->defaultAction();
    }

    /**
     * @return bool
     */
    private function validateFormSubmission()
    {
        $isValid = true;
        if (utf8_strlen($this->comment->getUser()) < 2) {
            $isValid = false;
            $this->messages .= XH_message('fail', $this->lang['error_user']);
        }
        $mailer = new Mailer(new MailHelper());
        if (!$mailer->isValidAddress($this->comment->getEmail())) {
            $isValid = false;
            $this->messages .= XH_message('fail', $this->lang['error_email']);
        }
        if (utf8_strlen($this->comment->getMessage()) < 2) {
            $isValid = false;
            $this->messages .= XH_message('fail', $this->lang['error_message']);
        }
        return $isValid && $this->validateCaptcha();
    }

    /**
     * @return bool
     */
    private function validateCaptcha()
    {
        $pluginname = $this->conf['captcha_plugin'];
        $filename = "{$this->pluginsFolder}$pluginname/captcha.php";
        if (!(defined('XH_ADM') && XH_ADM) && $pluginname && is_readable($filename)) {
            include_once $filename;
            if (!call_user_func($pluginname . '_captcha_check')) {
                $this->messages .= XH_message('fail', $this->lang['error_captcha']);
                return false;
            }
        }
        return true;
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
        $url = Url::getCurrent()->without('twocents_action')->getAbsolute();
        header("Location: $url", true, 303);
        exit;
    }
}
