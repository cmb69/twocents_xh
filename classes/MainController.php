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

    /** @var bool */
    private $jsWritten = false;

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
        if (!preg_match('/^[a-z0-9-]+$/i', $topic)) {
            return Response::create($this->view->message("fail", "error_topicname"));
        }
        switch ($_POST["twocents_action"] ?? "") {
            default:
                return $this->defaultAction($request, $topic, $readonly);
            case "toggle_visibility":
                return $this->toggleVisibilityAction($request, $topic);
            case "remove_comment":
                return $this->removeCommentAction($request, $topic);
            case "add_comment":
                return $this->addCommentAction($request, $topic, $readonly);
            case "update_comment":
                return $this->updateCommentAction($request, $topic, $readonly);
        }
    }

    private function toggleVisibilityAction(Request $request, string $topic): Response
    {
        if (!$request->admin()) {
            return Response::create("");
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
        return Response::createRedirect($url);
    }

    private function removeCommentAction(Request $request, string $topic): Response
    {
        if (!$request->admin()) {
            return Response::create("");
        }
        $this->csrfProtector->check();
        $comment = $this->db->findComment($topic, $_POST["twocents_id"]);
        $this->db->deleteComment($comment);
        $url = Url::getCurrent()->without('twocents_action')->getAbsolute();
        return Response::createRedirect($url);
    }

    private function defaultAction(
        Request $request,
        string $topic,
        bool $readonly,
        string $messages = "",
        ?Comment $current = null
    ): Response {
        if (isset($_GET['twocents_id'])) {
            $current = $this->db->findComment($topic, $_GET['twocents_id']);
        }
        $comments = $this->db->findCommentsOfTopic($topic, !$request->admin());
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
        $html .= $this->renderCommentsView($request, $comments, $readonly, $messages, $current);
        if (isset($pagination)) {
            $html .= $pagination;
        }
        if (!$this->isXmlHttpRequest()) {
            $response = Response::create("<div class=\"twocents_container\">$html</div>");
            if (!$this->jsWritten) {
                $response->setHjs($this->view->renderMeta("twocents", $this->jsConf()))
                    ->setBjs($this->view->renderScript($request->pluginsFolder() . "twocents/twocents.min.js"));
                $this->jsWritten = true;
            }
            return $response;
        } else {
            return Response::createContentType("text/html; charset=UTF-8")->setOutput($html);
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
        return $this->view->render('pagination', [
            'item_count' => $commentCount,
            'pages' => $this->pageRecords($pagination->gatherPages(), $url, $page),
        ]);
    }

    /**
     * @param list<int|null> $pages
     * @return list<array{index:?int,url:?Url,is_current:?bool,is_ellipsis:bool}>
     */
    private function pageRecords(array $pages, Url $url, int $currentPage): array
    {
        $records = [];
        foreach ($pages as $page) {
            if ($page !== null) {
                $records[] = [
                    'index' => $page,
                    'url' => (string) $url->without('twocents_id')->with('twocents_page', (string) $page),
                    'is_current' => $page === $currentPage,
                    'is_ellipsis' => false
                ];
            } else {
                $records[] = [
                    'index' => null,
                    "url" => null,
                    "isCurrent" => null,
                    'isEllipsis' => true
                ];
            }
        }
        return $records;
    }

    /** @param list<Comment> $comments */
    private function renderCommentsView(
        Request $request,
        array $comments,
        bool $readonly,
        string $messages,
        ?Comment $current = null
    ): string {
        $mayAddComment = (!isset($current) || $current->id() == null)
            && ($request->admin() || !$readonly);
        return $this->view->render('comments', [
            'comments' => $this->commentRecords($request, $comments, $current),
            'has_comment_form_above' => $mayAddComment && $this->conf['comments_order'] === 'DESC',
            'has_comment_form_below' => $mayAddComment && $this->conf['comments_order'] === 'ASC',
            'comment_form' => $mayAddComment ? $this->renderCommentForm($current) : null,
            'messages' => $messages,
        ]);
    }

    /**
     * @param list<Comment> $comments
     * @return list<array{isCurrent:bool,view:html}>
     */
    private function commentRecords(Request $request, array $comments, ?Comment $current):array
    {
        $records = [];
        foreach ($comments as $comment) {
            $isCurrentComment = $current !== null && $current->id() == $comment->id();
            $records[] = [
                'isCurrent' => $isCurrentComment,
                'view' => $this->renderCommentView($request, $comment, $isCurrentComment)
            ];
        }
        return $records;
    }

    /** @return array<string,scalar> */
    private function jsConf(): array
    {
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
        return $config;
    }

    private function renderCommentView(Request $request, Comment $comment, bool $isCurrentComment): string
    {
        $url = Url::getCurrent()->without('twocents_id');
        return $this->view->render('comment', [
            'id' => 'twocents_comment_' . $comment->id(),
            'css_class' => !$comment->hidden() ? '' : ' twocents_hidden',
            'is_current_comment' => $isCurrentComment,
            'form' => $isCurrentComment ? $this->renderCommentForm($comment) : null,
            'is_admin' => !$isCurrentComment ? $request->admin() : null,
            'url' => !$isCurrentComment ? (string) $url : null,
            'edit_url' => !$isCurrentComment ? (string) $url->with('twocents_id', $comment->id()) : null,
            'comment_id' => !$isCurrentComment ? $comment->id() : null,
            'visibility' => !$isCurrentComment ? (!$comment->hidden() ? 'label_hide' : 'label_show') : null,
            'attribution' => !$isCurrentComment ? $this->renderAttribution($comment) : null,
            'message' => !$isCurrentComment ? $this->renderMessage($comment) : null,
            'csrf_token' => $request->admin() ? $this->csrfProtector->token() : null,
        ]);
    }

    private function renderCommentForm(?Comment $comment = null): string
    {
        if (!isset($comment)) {
            $comment = new Comment("", "", 0, "", "", "", true);
        }
        $url = Url::getCurrent()->without('twocents_id');
        if (!$comment->id()) {
            $page = $this->conf['comments_order'] === 'ASC' ? '2147483647' : '0';
            $url = $url->with('twocents_page', $page);
        }
        return $this->view->render('comment-form', [
            'action' => $comment->id() ? 'update' : 'add',
            "label" => $comment->id() ? 'label_update' : 'label_add',
            "comment_id" => $comment->id(),
            "comment_user" => $comment->user(),
            "comment_email" => $comment->email(),
            "comment_message" => $comment->message(),
            'captcha' => $this->captcha->render(),
            "url" => (string) $url,
            "csrf_token" => $comment->id() ? $this->csrfProtector->token() : null,
        ]);
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

    private function addCommentAction(Request $request, string $topic, bool $readonly): Response
    {
        if (!$request->admin() && $readonly) {
            return $this->defaultAction($request, $topic, $readonly);
        }
        $message = trim($_POST['twocents_message']);
        if (!$request->admin() && $this->conf['comments_markup'] == 'HTML') {
            $message = $this->htmlCleaner->clean($message);
        }
        $hideComment = false;
        $isSpam = false;
        $spamFilter = new SpamFilter($this->lang['spam_words']);
        if (($this->conf['comments_moderated'] && !$request->admin())
                || ($isSpam = !$request->admin() && $spamFilter->isSpam($message))) {
            $hideComment = true;
        }
        $comment = new Comment(
            "",
            $topic,
            $request->time(),
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
            if (!$request->admin()) {
                $this->sendNotificationEmail($comment);
            }
            $comment = null;
            if (($this->conf['comments_moderated'] && !$request->admin()) || $isSpam) {
                $messages .= $this->view->message('info', 'message_moderated');
            } else {
                $messages .= $this->view->message('success', 'message_added');
            }
            $messages .= $marker;
        } else {
            $messages = $marker . $messages;
        }
        return $this->defaultAction($request, $topic, $readonly, $messages, $comment);
    }

    /** @return void */
    private function sendNotificationEmail(Comment $comment)
    {
        $email = $this->conf['email_address'];
        if ($email !== '') {
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

    private function updateCommentAction(Request $request, string $topic, bool $readonly): Response
    {
        if (!$request->admin()) {
            return Response::create("");
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
        return $this->defaultAction($request, $topic, $readonly, $messages, $comment);
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
