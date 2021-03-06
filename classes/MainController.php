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
use Pfw\Url;
use Pfw\View\View;
use Pfw\View\HtmlString;

class MainController extends Controller
{
    /**
     * @var string
     */
    private $topicname;

    /**
     * @var bool
     */
    private $readonly;

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
     * @param bool $readonly
     * @throws DomainException
     */
    public function __construct($topicname, $readonly)
    {
        parent::__construct();
        if (!$this->isValidTopicname($topicname)) {
            throw new DomainException;
        }
        $this->topicname = $topicname;
        $this->readonly = $readonly;
        $this->messages = '';
    }

    public function toggleVisibilityAction()
    {
        if (!XH_ADM) {
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
        if (!XH_ADM) {
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
        $comments = Comment::findByTopicname($this->topicname, true, $this->config['comments_order'] === 'ASC');
        $count = count($comments);
        $itemsPerPage = $this->config['pagination_max'];
        $pageCount = (int) ceil($count / $itemsPerPage);
        $currentPage = isset($_GET['twocents_page']) ? max(1, min($pageCount, $_GET['twocents_page'])) : 1;
        $comments = array_splice($comments, ($currentPage - 1) * $itemsPerPage, $itemsPerPage);
        $pagination = $this->preparePaginationView($count, $currentPage, $pageCount);
        ob_start();
        if (isset($pagination)) {
            $pagination->render();
        }
        $this->prepareCommentsView($comments)->render();
        if (isset($pagination)) {
            $pagination->render();
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
     * @return ?View
     */
    private function preparePaginationView($commentCount, $page, $pageCount)
    {
        if ($pageCount <= 1) {
            return null;
        }
        $pagination = new Pagination($page, $pageCount);
        $url = Url::getCurrent();
        $currentPage = $page;
        return (new View('twocents'))
            ->template('pagination')
            ->data([
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
     * @return View
     */
    private function prepareCommentsView(array $comments)
    {
        $this->writeScriptsToBjs();
        $mayAddComment = (!isset($this->comment) || $this->comment->getId() == null)
            && (XH_ADM || !$this->readonly);
        return (new View('twocents'))
            ->template('comments')
            ->data([
                'comments' => array_map(
                    function ($comment) {
                        return (object) array(
                            'isCurrent' => $this->isCurrentComment($comment),
                            'view' => $this->prepareCommentView($comment)
                        );
                    },
                    $comments
                ),
                'hasCommentFormAbove' => $mayAddComment && $this->config['comments_order'] === 'DESC',
                'hasCommentFormBelow' => $mayAddComment && $this->config['comments_order'] === 'ASC',
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
        $filename = "{$this->pluginsFolder}twocents/twocents.min.js";
        if (!file_exists($filename)) {
            $filename = "{$this->pluginsFolder}twocents/twocents.js";
        }
        ob_start();
        (new View('twocents'))
            ->template('scripts')
            ->data([
                'json' => new HtmlString(json_encode($config)),
                'filename' => $filename
            ])
            ->render();
        $bjs .= ob_get_clean();
    }

    /**
     * @return View
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
                'isAdmin' => XH_ADM,
                'url' => $url,
                'editUrl' => $url->with('twocents_id', $comment->getId()),
                'comment' => $comment,
                'visibility' => $comment->isVisible() ? 'label_hide' : 'label_show',
                'attribution' => new HtmlString($this->renderAttribution($comment)),
                'message' => new HtmlString($this->renderMessage($comment))
            ];
            if (XH_ADM) {
                $data['csrfTokenInput'] = new HtmlString($this->csrfProtector->tokenInput());
            }
        }
        return (new View('twocents'))
            ->template('comment')
            ->data($data);
    }

    /**
     * @return View
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
            $page = $this->config['comments_order'] === 'ASC' ? '2147483647' : '0';
            $data += [
                'url' => $url->with('twocents_page', $page),
                'csrfTokenInput' => ''
            ];
        }
        return (new View('twocents'))
            ->template('comment-form')
            ->data($data);
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
        if (!XH_ADM && $this->readonly) {
            $this->defaultAction();
        }
        $this->comment = Comment::make($this->topicname, time());
        $this->comment->setUser(trim($_POST['twocents_user']));
        $this->comment->setEmail(trim($_POST['twocents_email']));
        $message = trim($_POST['twocents_message']);
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
            $mailer = new Mailer(($this->config['email_linebreak'] === 'LF') ? "\n" : "\r\n");
            $mailer->send($email, $this->lang['email_subject'], $body, "From: $email\r\nReply-To: $replyTo");
        }
    }

    public function updateCommentAction()
    {
        if (!XH_ADM) {
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
        $url = Url::getCurrent()->without('twocents_action')->getAbsolute();
        header("Location: $url", true, 303);
        exit;
    }
}
