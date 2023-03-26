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

    /** @var CsrfProtector */
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

    /** @param array<string,string> $conf */
    public function __construct(
        string $pluginFolder,
        array $conf,
        CsrfProtector $csrfProtector,
        Db $db,
        HtmlCleaner $htmlCleaner,
        Random $random,
        Captcha $captcha,
        Mailer $mailer,
        View $view
    ) {
        $this->pluginFolder = $pluginFolder;
        $this->conf = $conf;
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
            return Response::create($this->view->error("error_topicname"));
        }
        switch ($request->action()) {
            default:
                return $this->defaultAction($request, $topic, $readonly);
            case "create":
                return $this->createComment($request, $readonly);
            case "do_create":
                return $this->addCommentAction($request, $topic, $readonly);
            case "edit":
                return $this->editCommentAction($request, $topic);
            case "do_edit":
                return $this->updateCommentAction($request, $topic);
            case "do_toggle_visibility":
                return $this->toggleVisibilityAction($request, $topic);
            case "do_delete":
                return $this->removeCommentAction($request, $topic);
        }
    }

    private function defaultAction(Request $request, string $topic, bool $readonly): Response
    {
        [$comments, $count, $page, $pageCount] = Util::limitComments(
            $this->db->findCommentsOfTopic($topic, !$request->admin()),
            (int) $this->conf['pagination_max'],
            is_string($request->url()->param("twocents_page")) ? (int) $request->url()->param("twocents_page") : 0,
            $this->conf['comments_order'] === 'ASC' ? 1 : -1
        );
        $pagination = $this->renderPaginationView($request->url(), $count, $page, $pageCount);
        $html = $pagination . $this->renderCommentsView($request, $comments, $readonly) . $pagination;
        return $this->respondWith($html);
    }

    private function renderPaginationView(Url $url, int $commentCount, int $page, int $pageCount): string
    {
        if ($pageCount <= 1) {
            return "";
        }
        $pagination = new Pagination($page, $pageCount, (int) $this->conf["pagination_radius"]);
        return $this->view->render('pagination', [
            'item_count' => $commentCount,
            'pages' => $this->paginationTuples($pagination->gatherPages(), $url, $page),
        ]);
    }

    /**
     * @param list<int|null> $pages
     * @return list<array{int|null,string|null}>
     */
    private function paginationTuples(array $pages, Url $url, int $currentPage): array
    {
        $url = $url->without("twocents_id");
        return array_map(function ($page) use ($url, $currentPage) {
            if ($page === null) {
                return [null, null];
            }
            return [$page, $page !== $currentPage ? $url->with("twocents_page", (string) $page)->relative() : null];
        }, $pages);
    }

    /** @param list<Comment> $comments */
    private function renderCommentsView(Request $request, array $comments, bool $readonly): string
    {
        $mayAddComment = $request->admin() || !$readonly;
        return $this->view->render('comments', [
            "module" => $this->pluginFolder . "twocents.min.js",
            'comments' => $this->commentRecords($request, $comments),
            'has_comment_form_above' => $mayAddComment && $this->conf['comments_order'] === 'DESC',
            'has_comment_form_below' => $mayAddComment && $this->conf['comments_order'] === 'ASC',
            "new_url" => $request->url()->with("twocents_action", "create")->relative(),
            "action_url" => $request->url()->without('twocents_id')->relative(),
            'is_admin' => $request->admin(),
            'csrf_token' => $request->admin() ? $this->csrfProtector->token() : null,
        ]);
    }

    /**
     * @param list<Comment> $comments
     * @return list<array{id:string,css_class:string,edit_url:string,visibility_action:string,delete_action:string,visibility:string,attribution:string,message:Html}>
     */
    private function commentRecords(Request $request, array $comments): array
    {
        $url = $request->url();
        return array_map(function (Comment $comment) use ($url) {
            assert($comment->id() !== null);
            $url = $url->with('twocents_id', $comment->id());
            return [
                'id' => 'twocents_comment_' . $comment->id(),
                'css_class' => !$comment->hidden() ? '' : ' twocents_hidden',
                'edit_url' => $url->with("twocents_action", "edit")->relative(),
                "visibility_action" => $url->with("twocents_action", "toggle_visibility")->relative(),
                "delete_action" => $url->with("twocents_action", "delete")->relative(),
                'visibility' => !$comment->hidden() ? 'label_hide' : 'label_show',
                'attribution' => $this->renderAttribution($comment),
                'message' => Html::of($this->renderMessage($comment)),
            ];
        }, $comments);
    }

    private function renderAttribution(Comment $comment): string
    {
        return strtr($this->view->plain("format_heading"), [
            '{DATE}' => date($this->view->plain("format_date"), $comment->time()),
            '{TIME}' => date($this->view->plain("format_time"), $comment->time()),
            '{USER}' => $comment->user()
        ]);
    }

    private function renderMessage(Comment $comment): string
    {
        if ($this->conf['comments_markup'] == 'HTML') {
            return $comment->message();
        } else {
            return (string) preg_replace('/(?:\r\n|\r|\n)/', "<br>", $this->view->esc($comment->message()));
        }
    }

    private function createComment(Request $request, bool $readonly): Response
    {
        if ($readonly && !$request->admin()) {
            return $this->respondWith($this->view->error("error_unauthorized"));
        }
        $comment = new Comment(null, "", 0, "", "", "", true);
        $html = $this->renderCommentForm($request, $comment);
        return $this->respondWith($html);
    }

    private function editCommentAction(Request $request, string $topic): Response
    {
        if (!$request->admin()) {
            return $this->respondWith($this->view->error("error_unauthorized"));
        }
        $comment = $this->db->findComment($topic, $request->commentId());
        if ($comment === null) {
            return $this->respondWith($this->view->error("error_no_comment"));
        }
        $html = $this->renderCommentForm($request, $comment);
        return $this->respondWith($html);
    }

    /** @param list<string> $errors */
    private function renderCommentForm(Request $request, Comment $comment, array $errors = []): string
    {
        $url = $request->url();
        $action = $comment->id() ? "edit" : "create";
        return $this->view->render('comment-form', [
            'action' => $comment->id() ? 'update' : 'add',
            "label" => $comment->id() ? 'label_update' : 'label_add',
            "comment_id" => $comment->id(),
            "comment_user" => $comment->user(),
            "comment_email" => $comment->email(),
            "comment_message" => $comment->message(),
            'captcha' => Html::of($this->captcha->render($request->admin())),
            "admin" => $request->admin(),
            "module" => $this->pluginFolder . "twocents.min.js",
            "url" => $url->with("twocents_action", $action)->relative(),
            "cancel_url" => $url->without("twocents_id")->without("twocents_action")->relative(),
            "csrf_token" => $request->admin() ? $this->csrfProtector->token() : null,
            "errors" => $errors,
            "conf" => $this->jsConf(),
        ]);
    }

    private function addCommentAction(Request $request, string $topic, bool $readonly): Response
    {
        if ($readonly && !$request->admin()) {
            return $this->respondWith($this->view->error("error_unauthorized"));
        }
        ["user" => $user, "email" => $email, "message" => $message] = $request->commentPost();
        if (!$request->admin() && $this->conf['comments_markup'] == 'HTML') {
            $message = $this->htmlCleaner->clean($message);
        }
        $spamFilter = new SpamFilter($this->view->plain("spam_words"));
        $hideComment = !$request->admin() && ($this->conf['comments_moderated'] || $spamFilter->isSpam($message));
        $comment = new Comment(null, $topic, $request->time(), $user, $email, $message, $hideComment);
        $errors = array_merge(
            Util::validateComment($comment),
            $this->captcha->check($request->admin()) ? [] : ["error_captcha"]
        );
        if (!empty($errors)) {
            return $this->respondWith($this->renderCommentForm($request, $comment, $errors));
        }
        $id = Util::encodeBase64url($this->random->bytes(15));
        $comment = $comment->withId($id);
        if (!$this->db->insertComment($comment)) {
            return $this->respondWith($this->renderCommentForm($request, $comment, ["error_store"]));
        }
        if (!$request->admin() && $this->conf['email_address']) {
            $this->sendNotificationEmail($request->url(), $comment);
        }
        $url = $request->url()->without("twocents_action")->absolute();
        return Response::redirect($url);
    }

    /** @return void */
    private function sendNotificationEmail(Url $url, Comment $comment)
    {
        $url = $url->without("twocents_action")->absolute() . "#twocents_comment_" . $comment->id();
        $message = $comment->message();
        if ($this->conf['comments_markup'] === 'HTML') {
            $message = Util::plainify($message);
        }
        $this->mailer->sendNotification(
            $this->conf['email_address'],
            $this->view->plain("email_subject"),
            $this->view->plain("email_attribution", $url, $comment->user(), $comment->email()),
            $message,
            $comment->email()
        );
    }

    private function updateCommentAction(Request $request, string $topic): Response
    {
        if (!$request->admin()) {
            return $this->respondWith($this->view->error("error_unauthorized"));
        }
        $this->csrfProtector->check();
        $comment = $this->db->findComment($topic, $request->commentId());
        if ($comment === null) {
            return $this->respondWith($this->view->error("error_no_comment"));
        }
        ["user" => $user, "email" => $email, "message" => $message] = $request->commentPost();
        $comment = $comment->with($user, $email, $message);
        $errors = array_merge(
            Util::validateComment($comment),
            $this->captcha->check($request->admin()) ? [] : ["error_captcha"]
        );
        if ($errors) {
            return $this->respondWith($this->renderCommentForm($request, $comment, $errors));
        }
        if (!$this->db->updateComment($comment)) {
            return $this->respondWith($this->renderCommentForm($request, $comment, ["error_store"]));
        }
        $url = $request->url()->without("twocents_id")->without("twocents_action")->absolute();
        return Response::redirect($url);
    }

    private function respondWith(string $html): Response
    {
        if ($this->isXmlHttpRequest()) {
            return Response::create($html)->withContentType("text/html; charset=UTF-8");
        }
        return Response::create("<div class=\"twocents_container\">\n$html</div>\n");
    }

    private function isXmlHttpRequest(): bool
    {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH'])
            && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest';
    }

    /** @return array<string,scalar> */
    private function jsConf(): array
    {
        $config = ["comments_markup" => $this->conf["comments_markup"]];
        foreach (["label_bold", "label_italic", "label_link", "label_unlink", "message_link"] as $property) {
            $config[$property] = $this->view->plain($property);
        }
        return $config;
    }

    private function toggleVisibilityAction(Request $request, string $topic): Response
    {
        if (!$request->admin()) {
            return $this->respondWith($this->view->error("error_unauthorized"));
        }
        $this->csrfProtector->check();
        $comment = $this->db->findComment($topic, $request->commentId());
        if ($comment === null) {
            return $this->respondWith($this->view->error("error_no_comment"));
        }
        $comment = $comment->withToggledVisibility();
        if (!$this->db->updateComment($comment)) {
            return $this->respondWith($this->renderCommentForm($request, $comment, ["error_store"]));
        }
        $url = $request->url()->without("twocents_id")->without('twocents_action')->absolute();
        return Response::redirect($url);
    }

    private function removeCommentAction(Request $request, string $topic): Response
    {
        if (!$request->admin()) {
            return $this->respondWith($this->view->error("error_unauthorized"));
        }
        $this->csrfProtector->check();
        $comment = $this->db->findComment($topic, $request->commentId());
        if ($comment === null) {
            return $this->respondWith($this->view->error("error_no_comment"));
        }
        if (!$this->db->deleteComment($comment)) {
            return $this->respondWith($this->renderCommentForm($request, $comment, ["error_store"]));
        }
        $url = $request->url()->without("twocents_id")->without('twocents_action')->absolute();
        return Response::redirect($url);
    }
}
