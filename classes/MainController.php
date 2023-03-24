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
use Twocents\Infra\Mailer;
use Twocents\Infra\Random;
use Twocents\Infra\Request;
use Twocents\Infra\View;
use Twocents\Logic\Pagination;
use Twocents\Logic\SpamFilter;
use Twocents\Logic\Util;
use Twocents\Value\Comment;
use Twocents\Value\Html;
use Twocents\Value\Response;
use Twocents\Value\Url;

class MainController
{
    /** @var string */
    private $pluginFolder;

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

    /** @var Random */
    private $random;

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
        string $pluginFolder,
        array $conf,
        array $lang,
        ?CsrfProtector $csrfProtector,
        Db $db,
        HtmlCleaner $htmlCleaner,
        Random $random,
        Captcha $captcha,
        Mailer $mailer,
        View $view
    ) {
        $this->pluginFolder = $pluginFolder;
        $this->conf = $conf;
        $this->lang = $lang;
        $this->csrfProtector = $csrfProtector;
        $this->db = $db;
        $this->htmlCleaner = $htmlCleaner;
        $this->random = $random;
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
        $url = $request->url()->without('twocents_action')->absolute();
        return Response::redirect($url);
    }

    private function removeCommentAction(Request $request, string $topic): Response
    {
        if (!$request->admin()) {
            return Response::create("");
        }
        $this->csrfProtector->check();
        $comment = $this->db->findComment($topic, $_POST["twocents_id"]);
        $this->db->deleteComment($comment);
        $url = $request->url()->without('twocents_action')->absolute();
        return Response::redirect($url);
    }

    private function defaultAction(
        Request $request,
        string $topic,
        bool $readonly,
        string $messages = "",
        ?Comment $current = null
    ): Response {
        if (is_string($request->url()->param("twocents_id"))) {
            $current = $this->db->findComment($topic, $request->url()->param("twocents_id"));
        }
        $comments = $this->db->findCommentsOfTopic($topic, !$request->admin());
        $order = $this->conf['comments_order'] === 'ASC' ? 1 : -1;
        usort($comments, function ($a, $b) use ($order) {
            return ($a->time() - $b->time()) * $order;
        });
        $count = count($comments);
        $itemsPerPage = (int) $this->conf['pagination_max'];
        $pageCount = (int) ceil($count / $itemsPerPage);
        $currentPage = is_string($request->url()->param("twocents_page"))
            ? max(1, min($pageCount, (int) $request->url()->param("twocents_page")))
            : 1;
        $comments = array_splice($comments, ($currentPage - 1) * $itemsPerPage, $itemsPerPage);
        $pagination = $this->renderPaginationView($request->url(), $count, $currentPage, $pageCount);
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
                $response = $response->withHjs($this->view->renderMeta("twocents", $this->jsConf()))
                    ->withBjs($this->view->renderScript($this->pluginFolder . "twocents.min.js"));
                $this->jsWritten = true;
            }
            return $response;
        } else {
            return Response::create($html)->withContentType("text/html; charset=UTF-8");
        }
    }

    /** @return string|null */
    private function renderPaginationView(Url $url, int $commentCount, int $page, int $pageCount)
    {
        if ($pageCount <= 1) {
            return null;
        }
        $pagination = new Pagination($page, $pageCount, (int) $this->conf["pagination_radius"]);
        return $this->view->render('pagination', [
            'item_count' => $commentCount,
            'pages' => $this->pageRecords($pagination->gatherPages(), $url, $page),
        ]);
    }

    /**
     * @param list<int|null> $pages
     * @return list<array{index:?int,url:?string,is_current:?bool,is_ellipsis:bool}>
     */
    private function pageRecords(array $pages, Url $url, int $currentPage): array
    {
        $records = [];
        foreach ($pages as $page) {
            if ($page !== null) {
                $records[] = [
                    'index' => $page,
                    'url' => $url->without('twocents_id')->with('twocents_page', (string) $page)->relative(),
                    'is_current' => $page === $currentPage,
                    'is_ellipsis' => false
                ];
            } else {
                $records[] = [
                    'index' => null,
                    "url" => null,
                    "is_current" => null,
                    'is_ellipsis' => true
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
            'comment_form' => $mayAddComment ? Html::of($this->renderCommentForm($request->url(), $current)) : null,
            'messages' => Html::of($messages),
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
                'view' => Html::of($this->renderCommentView($request, $comment, $isCurrentComment))
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
        $url = $request->url()->without('twocents_id');
        return $this->view->render('comment', [
            'id' => 'twocents_comment_' . $comment->id(),
            'css_class' => !$comment->hidden() ? '' : ' twocents_hidden',
            'is_current_comment' => $isCurrentComment,
            'form' => $isCurrentComment ? Html::of($this->renderCommentForm($request->url(), $comment)) : null,
            'is_admin' => !$isCurrentComment ? $request->admin() : null,
            'url' => !$isCurrentComment ? $url->relative() : null,
            'edit_url' => !$isCurrentComment ? $url->with('twocents_id', $comment->id())->relative() : null,
            'comment_id' => !$isCurrentComment ? $comment->id() : null,
            'visibility' => !$isCurrentComment ? (!$comment->hidden() ? 'label_hide' : 'label_show') : null,
            'attribution' => !$isCurrentComment ? Html::of($this->renderAttribution($comment)) : null,
            'message' => !$isCurrentComment ? Html::of($this->renderMessage($comment)) : null,
            'csrf_token' => $request->admin() ? $this->csrfProtector->token() : null,
        ]);
    }

    private function renderCommentForm(Url $url, ?Comment $comment = null): string
    {
        if (!isset($comment)) {
            $comment = new Comment("", "", 0, "", "", "", true);
        }
        $url = $url->without('twocents_id');
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
            'captcha' => Html::of($this->captcha->render()),
            "url" => $url->relative(),
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
        ["user" => $user, "email" => $email, "message" => $message] = $request->commentPost();
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
        $id = Util::encodeBase64url($this->random->bytes(15));
        $comment = new Comment($id, $topic, $request->time(), $user, $email, $message, $hideComment);
        $marker = '<div id="twocents_scroll_marker" class="twocents_scroll_marker">'
            . '</div>';
        $messages = $this->validateComment($comment);
        if (empty($messages)) {
            $this->db->insertComment($comment);
            if (!$request->admin()) {
                $this->sendNotificationEmail($request->url(), $comment);
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
    private function sendNotificationEmail(Url $url, Comment $comment)
    {
        $email = $this->conf['email_address'];
        if ($email !== '') {
            $url = $url->absolute() . "#twocents_comment_" . $comment->id();
            $message = $comment->message();
            if ($this->conf['comments_markup'] === 'HTML') {
                $message = strip_tags($message);
            }
            $this->mailer->sendNotification(
                $email,
                $this->view->plain("email_subject"),
                $this->view->plain("email_attribution", $url, $comment->user(), $comment->email()),
                $message,
                $comment->email()
            );
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
        ["user" => $user, "email" => $email, "message" => $message] = $request->commentPost();
        $comment = $comment->withUser($user)->withEmail($email)->withMessage($message);
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
