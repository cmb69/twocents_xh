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

class MainController extends Controller
{
    /**
     * @var string
     */
    private $topicname;

    /**
     * @var Comment
     */
    private $comment;

    /**
     * @var string
     */
    private $messages;

    /**
     * @param string $topicname
     * @throws DomainException
     */
    public function __construct($topicname)
    {
        parent::__construct();
        if (!$this->isValidTopicname($topicname)) {
            throw new DomainException;
        }
        $this->topicname = $topicname;
        $this->messages = '';
    }

    public function toggleVisibilityAction()
    {
        if (!XH_ADM) {
            return;
        }
        $this->csrfProtector->check();
        $comment = Comment::find(stsl($_POST['twocents_id']), $this->topicname);
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
        if (!XH_ADM) {
            return;
        }
        $this->csrfProtector->check();
        $comment = Comment::find(stsl($_POST['twocents_id']), $this->topicname);
        if (isset($comment)) {
            $comment->delete();
        }
        $this->redirectToDefault();
    }

    public function defaultAction()
    {
        if (isset($_GET['twocents_id'])) {
            $this->comment = Comment::find(stsl($_GET['twocents_id']), $this->topicname);
        }
        $comments = Comment::findByTopicname($this->topicname, true, $this->config['comments_order'] === 'ASC');
        $count = count($comments);
        $itemsPerPage = $this->config['pagination_max'];
        $pageCount = (int) ceil($count / $itemsPerPage);
        $currentPage = filter_input(
            INPUT_GET,
            'twocents_page',
            FILTER_VALIDATE_INT,
            array('options' => array('min_range' => 1, 'max_range' => $pageCount, 'default' => 1))
        );
        $comments = array_splice($comments, ($currentPage - 1) * $itemsPerPage, $itemsPerPage);
        $paginationView = $this->preparePaginationView($count, $currentPage, $pageCount);
        $html = $paginationView . $this->prepareCommentsView($comments) . $paginationView;
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
     * @return View
     */
    private function preparePaginationView($commentCount, $page, $pageCount)
    {
        if ($pageCount <= 1) {
            return '';
        }
        $view = new View('pagination');
        $view->itemCount = $commentCount;
        $view->currentPage = $page;
        $pagination = new Pagination($page, $pageCount);
        $url = new Url($this->scriptName, $_GET);
        $view->pages = array_map(
            function ($page) use ($url) {
                if (isset($page)) {
                    return (object) array(
                        'index' => $page,
                        'url' => $url->without('twocents_id')->with('twocents_page', $page)
                    );
                } else {
                    return null;
                }
            },
            $pagination->gatherPages()
        );
        return $view;
    }

    /**
     * @param Comment[] $comments
     * @return View
     */
    private function prepareCommentsView(array $comments)
    {
        $this->writeScriptsToBjs();
        $view = new View('comments');
        $view->comments = array_map(
            function ($comment) {
                return (object) array(
                    'isCurrent' => $this->isCurrentComment($comment),
                    'view' => $this->prepareCommentView($comment)
                );
            },
            $comments
        );
        $mayAddComment = !isset($this->comment) || $this->comment->getId() == null;
        $view->hasCommentFormAbove = $mayAddComment && $this->config['comments_order'] === 'DESC';
        $view->hasCommentFormBelow = $mayAddComment && $this->config['comments_order'] === 'ASC';
        if ($mayAddComment) {
            $view->commentForm = $this->prepareCommentForm($this->comment);
        }
        $view->messages = new HtmlString($this->messages);
        return $view;
    }

    private function writeScriptsToBjs()
    {
        global $bjs;

        $config = array();
        foreach (array('comments_markup') as $property) {
            $config[$property] = $this->config[$property];
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
        $view = new View('scripts');
        $view->json = new HtmlString(json_encode($config));
        $view->filename = "{$this->pluginsFolder}twocents/twocents.js";
        $bjs .= $view;
    }

    /**
     * @return View
     */
    private function prepareCommentView(Comment $comment)
    {
        $view = new View('comment');
        $isCurrentComment = $this->isCurrentComment($comment);
        $view->id = 'twocents_comment_' . $comment->getId();
        $view->className = $comment->isVisible() ? '' : ' twocents_hidden';
        $view->isCurrentComment = $isCurrentComment;
        if ($isCurrentComment) {
            $view->form = $this->prepareCommentForm($this->comment);
        } else {
            $view->isAdmin = XH_ADM;
            $view->url = (new Url($this->scriptName, $_GET))->without('twocents_id');
            $view->editUrl = $view->url->with('twocents_id', $comment->getId());
            $view->comment = $comment;
            if (XH_ADM) {
                $view->csrfTokenInput = new HtmlString($this->csrfProtector->tokenInput());
            }
            $view->visibility = $comment->isVisible() ? 'label_hide' : 'label_show';
            $view->attribution = new HtmlString($this->renderAttribution($comment));
            $view->message = new HtmlString($this->renderMessage($comment));
        }
        return $view;
    }

    /**
     * @return View
     */
    private function prepareCommentForm(Comment $comment = null)
    {
        if (!isset($comment)) {
            $comment = Comment::make(null, null);
        }
        $view = new View('comment-form');
        $view->action = $comment->getId() ? 'update' : 'add';
        $view->url = (new Url($this->scriptName, $_GET))->without('twocents_id');
        $view->comment = $comment;
        $view->captcha = new HtmlString($this->renderCaptcha());
        if ($comment->getId()) {
            $view->csrfTokenInput = new HtmlString($this->csrfProtector->tokenInput());
        } else {
            $view->csrfTokenInput = '';
        }
        return $view;
    }

    /**
     * @return string
     */
    private function renderCaptcha()
    {
        $pluginname = $this->config['captcha_plugin'];
        $filename = "{$this->pluginsFolder}$pluginname/captcha.php";
        if (!XH_ADM && $pluginname && is_readable($filename)) {
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
        if ($this->config['comments_markup'] == 'HTML') {
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
        $this->comment = Comment::make($this->topicname, time());
        $this->comment->setUser(trim(stsl($_POST['twocents_user'])));
        $this->comment->setEmail(trim(stsl($_POST['twocents_email'])));
        $message = trim(stsl($_POST['twocents_message']));
        if (!XH_ADM && $this->config['comments_markup'] == 'HTML') {
            $message = $this->purify($message);
        }
        $this->comment->setMessage($message);
        $spamFilter = new SpamFilter;
        if ($this->isModerated() || ($isSpam = !XH_ADM && $spamFilter->isSpam($message))) {
            $this->comment->hide();
        }
        $marker = '<div id="twocents_scroll_marker" class="twocents_scroll_marker">'
            . '</div>';
        if ($this->validateFormSubmission()) {
            $this->comment->insert();
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
        return $this->config['comments_moderated'] && !XH_ADM;
    }

    private function sendNotificationEmail()
    {
        $email = $this->config['email_address'];
        if (!XH_ADM && $email !== '') {
            $message = $this->comment->getMessage();
            if ($this->config['comments_markup'] === 'HTML') {
                $message = strip_tags($message);
            }
            $view = new PlainTextView('email');
            $view->comment = $this->comment;
            $view->url = (new Url($this->scriptName, $_GET))->absolute()
                . "#twocents_comment_" . $this->comment->getId();
            $view->message = '> ' . str_replace("\n", "\n> ", $message);
            $replyTo = str_replace(["\n", "\r"], '', $this->comment->getEmail());
            $mailer = new Mailer(($this->config['email_linebreak'] === 'LF') ? "\n" : "\r\n");
            $mailer->send($email, $this->lang['email_subject'], $view, "From: $email\r\nReply-To: $replyTo");
        }
    }

    public function updateCommentAction()
    {
        if (!XH_ADM) {
            return;
        }
        $this->csrfProtector->check();
        $this->comment = Comment::find(stsl($_POST['twocents_id']), $this->topicname);
        $this->comment->setUser(trim(stsl($_POST['twocents_user'])));
        $this->comment->setEmail(trim(stsl($_POST['twocents_email'])));
        $this->comment->setMessage(trim(stsl($_POST['twocents_message'])));
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
        $mailer = new Mailer();
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
        $pluginname = $this->config['captcha_plugin'];
        $filename = "{$this->pluginsFolder}$pluginname/captcha.php";
        if (!XH_ADM && $pluginname && is_readable($filename)) {
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
        $url = (new Url($this->scriptName, $_GET))->without('twocents_action')->absolute();
        header("Location: $url", true, 303);
        exit;
    }
}
