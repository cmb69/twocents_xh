<?php

/**
 * Copyright 2014-2023 Christoph M. Becker
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

use Twocents\Infra\Captcha;
use Twocents\Infra\CsrfProtector;
use Twocents\Infra\Db;
use Twocents\Infra\HtmlCleaner;
use Twocents\Infra\Request;
use Twocents\Infra\Response;
use Twocents\Infra\Url;
use Twocents\Infra\View;
use Twocents\Logic\Pagination;
use Twocents\Logic\SpamFilter;
use Twocents\Logic\Util;
use Twocents\Value\Comment;
use Twocents\Value\HtmlString;
use XH\Mail as Mailer;

class MainController
{
    /** @var array<string,string> */
    private $conf;

    /** @var array<string,string> */
    private $lang;

    /** @var CsrfProtector|null */
    private $csrfProtector;

    /** @var Db */
    private $db;

    /** @var HtmlCleaner */
    private $htmlCleaner;

    /** @var Captcha */
    private $captcha;

    /** @var Mailer */
    private $mailer;

    /** @var View */
    private $view;

    /** @var Request */
    private $request;

    /** @var Response */
    private $response;

    /**
     * @param array<string,string> $conf
     * @param array<string,string> $lang
     */
    public function __construct(
        array $conf,
        array $lang,
        ?CsrfProtector $csrfProtector,
        Db $db,
        HtmlCleaner $htmlCleaner,
        Captcha $captcha,
        Mailer $mailer,
        View $view
    ) {
        $this->conf = $conf;
        $this->lang = $lang;
        $this->csrfProtector = $csrfProtector;
        $this->db = $db;
        $this->htmlCleaner = $htmlCleaner;
        $this->captcha = $captcha;
        $this->mailer = $mailer;
        $this->view = $view;
    }

    public function __invoke(Request $request, string $topic, bool $readonly): Response
    {
        $this->request = $request;
        $this->response = new Response;
        if (!preg_match('/^[a-z0-9-]+$/i', $topic)) {
            return $this->response->setOutput($this->view->message("fail", "error_topicname"));
        }
        switch ($_POST["twocents_action"] ?? "") {
            default:
                $this->defaultAction($topic, $readonly);
                break;
            case "toggle_visibility":
                $this->toggleVisibilityAction($topic);
                break;
            case "remove_comment":
                $this->removeCommentAction($topic);
                break;
            case "add_comment":
                $this->addCommentAction($topic, $readonly);
                break;
            case "update_comment":
                $this->updateCommentAction($topic, $readonly);
                break;
        }
        return $this->response;
    }

    /** @return void */
    private function toggleVisibilityAction(string $topic)
    {
        if (!$this->request->admin()) {
            return;
        }
        $this->csrfProtector->check();
        $comment = $this->db->findComment($topic, $_POST["twocents_id"]);
        if ($comment->hidden()) {
            $comment = $comment->show();
        } else {
            $comment = $comment->hide();
        }
        $this->db->updateComment($comment);
        $url = Url::getCurrent()->without('twocents_action')->getAbsolute();
        $this->response->redirect($url);
    }

    /** @return void */
    private function removeCommentAction(string $topic)
    {
        if (!$this->request->admin()) {
            return;
        }
        $this->csrfProtector->check();
        $comment = $this->db->findComment($topic, $_POST["twocents_id"]);
        $this->db->deleteComment($comment);
        $url = Url::getCurrent()->without('twocents_action')->getAbsolute();
        $this->response->redirect($url);
    }

    /** @return void */
    private function defaultAction(string $topic, bool $readonly, string $messages = "", ?Comment $current = null)
    {
        if (isset($_GET['twocents_id'])) {
            $current = $this->db->findComment($topic, $_GET['twocents_id']);
        }
        $comments = $this->db->findCommentsOfTopic($topic, !$this->request->admin());
        $order = $this->conf['comments_order'] === 'ASC' ? 1 : -1;
        usort($comments, function ($a, $b) use ($order) {
            return ($a->time() - $b->time()) * $order;
        });
        $count = count($comments);
        $itemsPerPage = (int) $this->conf['pagination_max'];
        $pageCount = (int) ceil($count / $itemsPerPage);
        $currentPage = isset($_GET['twocents_page']) ? max(1, min($pageCount, $_GET['twocents_page'])) : 1;
        $comments = array_splice($comments, ($currentPage - 1) * $itemsPerPage, $itemsPerPage);
        $pagination = $this->renderPaginationView($count, $currentPage, $pageCount);
        $html = "";
        if (isset($pagination)) {
            $html .= $pagination;
        }
        $html .= $this->renderCommentsView($comments, $readonly, $messages, $current);
        if (isset($pagination)) {
            $html .= $pagination;
        }
        if (!$this->isXmlHttpRequest()) {
            $this->response->setOutput("<div class=\"twocents_container\">$html</div>");
        } else {
            $this->response->setContentType("text/html; charset=UTF-8")->setOutput($html);
        }
    }

    /** @return string|null */
    private function renderPaginationView(int $commentCount, int $page, int $pageCount)
    {
        if ($pageCount <= 1) {
            return null;
        }
        $pagination = new Pagination($page, $pageCount, (int) $this->conf["pagination_radius"]);
        $url = Url::getCurrent();
        $currentPage = $page;
        return $this->view->render('pagination', [
            'itemCount' => $commentCount,
            'pages' => array_map(
                function ($page) use ($url, $currentPage) {
                    if (isset($page)) {
                        return (object) array(
                            'index' => $page,
                            'url' => $url->without('twocents_id')->with('twocents_page', (string) $page),
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

    /** @param list<Comment> $comments */
    private function renderCommentsView(array $comments, bool $readonly, string $messages, ?Comment $current = null): string
    {
        $this->writeScriptsToBjs($this->request->pluginsFolder());
        $mayAddComment = (!isset($current) || $current->id() == null)
            && ($this->request->admin() || !$readonly);
        return $this->view->render('comments', [
            'comments' => array_map(
                function ($comment) use ($current) {
                    $isCurrentComment = isset($current) && $current->id() == $comment->id();
                    return (object) array(
                        'isCurrent' => $isCurrentComment,
                        'view' => $this->renderCommentView($comment, $isCurrentComment)
                    );
                },
                $comments
            ),
            'hasCommentFormAbove' => $mayAddComment && $this->conf['comments_order'] === 'DESC',
            'hasCommentFormBelow' => $mayAddComment && $this->conf['comments_order'] === 'ASC',
            'commentForm' => $mayAddComment ? $this->renderCommentForm($current) : null,
            'messages' => new HtmlString($messages)
        ]);
    }

    /** @return void */
    private function writeScriptsToBjs(string $pluginsFolder)
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
        $filename = "{$pluginsFolder}twocents/twocents.min.js";
        if (!file_exists($filename)) {
            $filename = "{$pluginsFolder}twocents/twocents.js";
        }
        $bjs .= $this->view->render('scripts', [
            'json' => new HtmlString((string) json_encode($config)),
            'filename' => $filename
        ]);
    }

    private function renderCommentView(Comment $comment, bool $isCurrentComment): string
    {
        $data = [
            'id' => 'twocents_comment_' . $comment->id(),
            'className' => !$comment->hidden() ? '' : ' twocents_hidden',
            'isCurrentComment' => $isCurrentComment
        ];
        if ($isCurrentComment) {
            $data['form'] = $this->renderCommentForm($comment);
        } else {
            $url = Url::getCurrent()->without('twocents_id');
            $data += [
                'isAdmin' => $this->request->admin(),
                'url' => $url,
                'editUrl' => $url->with('twocents_id', $comment->id()),
                'comment' => $comment,
                'visibility' => !$comment->hidden() ? 'label_hide' : 'label_show',
                'attribution' => new HtmlString($this->renderAttribution($comment)),
                'message' => new HtmlString($this->renderMessage($comment)),
            ];
            if ($this->request->admin()) {
                $data['csrfToken'] = $this->csrfProtector->token();
            }
        }
        return $this->view->render('comment', $data);
    }

    private function renderCommentForm(?Comment $comment = null): string
    {
        if (!isset($comment)) {
            $comment = new Comment("", "", 0, "", "", "", true);
        }
        $url = Url::getCurrent()->without('twocents_id');
        $data = [
            'action' => $comment->id() ? 'update' : 'add',
            'comment' => $comment,
            'captcha' => new HtmlString($this->captcha->render()),
        ];
        if ($comment->id()) {
            $data += [
                'url' => $url,
                'csrfToken' => $this->csrfProtector->token(),
            ];
        } else {
            $page = $this->conf['comments_order'] === 'ASC' ? '2147483647' : '0';
            $data += [
                'url' => $url->with('twocents_page', $page),
                'csrfToken' => null
            ];
        }
        return $this->view->render('comment-form', $data);
    }

    private function renderAttribution(Comment $comment): string
    {
        $date = date($this->lang['format_date'], $comment->time());
        $time = date($this->lang['format_time'], $comment->time());
        return strtr(
            $this->lang['format_heading'],
            array(
                '{DATE}' => $date,
                '{TIME}' => $time,
                '{USER}' => $this->view->esc($comment->user())
            )
        );
    }

    private function renderMessage(Comment $comment): string
    {
        if ($this->conf['comments_markup'] == 'HTML') {
            return $comment->message();
        } else {
            return preg_replace('/(?:\r\n|\r|\n)/', "<br>", $this->view->esc($comment->message()));
        }
    }

    /** @return void */
    private function addCommentAction(string $topic, bool $readonly)
    {
        if (!$this->request->admin() && $readonly) {
            $this->defaultAction($topic, $readonly);
            return;
        }
        $message = trim($_POST['twocents_message']);
        if (!$this->request->admin() && $this->conf['comments_markup'] == 'HTML') {
            $message = $this->htmlCleaner->clean($message);
        }
        $hideComment = false;
        $isSpam = false;
        $spamFilter = new SpamFilter($this->lang['spam_words']);
        if ($this->isModerated() || ($isSpam = !$this->request->admin() && $spamFilter->isSpam($message))) {
            $hideComment = true;
        }
        $comment = new Comment(
            "",
            $topic,
            $this->request->time(),
            trim($_POST['twocents_user']),
            trim($_POST['twocents_email']),
            $message,
            $hideComment
        );
        $marker = '<div id="twocents_scroll_marker" class="twocents_scroll_marker">'
            . '</div>';
        $messages = $this->validateComment($comment);
        if (empty($messages)) {
            $this->db->insertComment($comment);
            $this->sendNotificationEmail($comment);
            $comment = null;
            if ($this->isModerated() || $isSpam) {
                $messages .= $this->view->message('info', 'message_moderated');
            } else {
                $messages .= $this->view->message('success', 'message_added');
            }
            $messages .= $marker;
        } else {
            $messages = $marker . $messages;
        }
        $this->defaultAction($topic, $readonly, $messages, $comment);
    }

    private function isModerated(): bool
    {
        return $this->conf['comments_moderated'] && !$this->request->admin();
    }

    /** @return void */
    private function sendNotificationEmail(Comment $comment)
    {
        $email = $this->conf['email_address'];
        if (!$this->request->admin() && $email !== '') {
            $message = $comment->message();
            if ($this->conf['comments_markup'] === 'HTML') {
                $message = strip_tags($message);
            }
            $url = Url::getCurrent()->getAbsolute()
                . "#twocents_comment_" . $comment->id();
            $attribution = sprintf(
                $this->lang['email_attribution'],
                $url,
                $comment->user(),
                $comment->email()
            );
            $body = $attribution. "\n\n> " . str_replace("\n", "\n> ", $message);
            $replyTo = str_replace(["\n", "\r"], '', $comment->email());
            $this->mailer->setTo($email);
            $this->mailer->setSubject($this->lang['email_subject']);
            $this->mailer->setMessage($body);
            $this->mailer->addHeader("From", $email);
            $this->mailer->addHeader("Reply-To", $replyTo);
            $this->mailer->send();
        }
    }

    /** @return void */
    private function updateCommentAction(string $topic, bool $readonly)
    {
        if (!$this->request->admin()) {
            return;
        }
        $this->csrfProtector->check();
        $comment = $this->db->findComment($topic, $_POST["twocents_id"]);
        assert(isset($comment)); // TODO: invalid assertion, but the code already was broken
        $comment = $comment->withUser(trim($_POST['twocents_user']))
            ->withEmail(trim($_POST['twocents_email']))
            ->withMessage(trim($_POST['twocents_message']));
        $messages = $this->validateComment($comment);
        if (empty($messages)) {
            $this->db->updateComment($comment);
            $comment = null;
        }
        $this->defaultAction($topic, $readonly, $messages, $comment);
    }

    private function validateComment(Comment $comment): string
    {
        $messages = "";
        $errors = Util::validateComment($comment);
        foreach ($errors as $error) {
            $messages .= $this->view->message("fail", $error);
        }
        return $messages . $this->validateCaptcha();
    }

    private function validateCaptcha(): string
    {
        if ($this->captcha->check()) {
            return "";
        }
        return $this->view->message('fail', 'error_captcha');
    }

    private function isXmlHttpRequest(): bool
    {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH'])
            && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest';
    }
}
