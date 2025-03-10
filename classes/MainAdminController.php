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

use Plib\Request;
use Twocents\Infra\CsrfProtector;
use Twocents\Infra\Db;
use Twocents\Infra\FlashMessage;
use Twocents\Infra\HtmlCleaner;
use Twocents\Infra\View;
use Twocents\Logic\Util;
use Twocents\Value\Html;
use Twocents\Value\Response;

class MainAdminController
{
    /** @var array<string,string> */
    private $conf;

    /** @var CsrfProtector */
    private $csrfProtector;

    /** @var Db */
    private $db;

    /** @var HtmlCleaner */
    private $htmlCleaner;

    /** @var FlashMessage */
    private $flashMessage;

    /** @var View */
    private $view;

    /** @param array<string,string> $conf */
    public function __construct(
        array $conf,
        CsrfProtector $csrfProtector,
        Db $db,
        HtmlCleaner $htmlCleaner,
        FlashMessage $flashMessage,
        View $view
    ) {
        $this->conf = $conf;
        $this->csrfProtector = $csrfProtector;
        $this->db = $db;
        $this->htmlCleaner = $htmlCleaner;
        $this->flashMessage = $flashMessage;
        $this->view = $view;
    }

    public function __invoke(Request $request): Response
    {
        switch ($this->action($request)) {
            default:
                return $this->overview();
            case "convert_to_html":
                return $this->convertTo("html");
            case "do_convert_to_html":
                return $this->doConvertTo($request, "html");
            case "convert_to_plain_text":
                return $this->convertTo("plain");
            case "do_convert_to_plain_text":
                return $this->doConvertTo($request, "plain");
            case "import_comments":
                return $this->importComments();
            case "do_import_comments":
                return $this->doImportComments($request);
            case "import_gbook":
                return $this->importGbook();
            case "do_import_gbook":
                return $this->doImportGbook($request);
        }
    }

    private function action(Request $request): string
    {
        $action = $request->get("twocents_action");
        if ($action === null) {
            return "";
        }
        if (!strncmp($action, "do_", strlen("do_"))) {
            return "";
        }
        if ($request->post("twocents_do") !== null) {
            return "do_$action";
        }
        return $action;
    }

    private function overview(): Response
    {
        if ($this->conf['comments_markup'] === 'HTML') {
            $button = 'convert_to_plain_text';
        } else {
            $button = 'convert_to_html';
        }
        return Response::create($this->view->render('admin', [
            "flash_message" => Html::of($this->flashMessage->pop()),
            "buttons" => [
                ["value" => $button, "label" => "label_$button"],
                ["value" => "import_comments", "label" => "label_import_comments"],
                ["value" => "import_gbook", "label" => "label_import_gbook"],
            ],
        ]))->withTitle("Twocents – " . $this->view->text("menu_main"));
    }

    private function convertTo(string $to): Response
    {
        return Response::create($this->view->render("confirm", [
            "csrf_token" => $this->csrfProtector->token(),
            "message_key" => "message_topics_to_convert",
            "count" => count($this->db->findTopics()),
            "key" => $to === "html" ? "label_convert_to_html" : "label_convert_to_plain_text",
        ]))->withTitle("Twocents – " . $this->view->text("menu_main"));
    }

    private function doConvertTo(Request $request, string $to): Response
    {
        $this->csrfProtector->check();
        $count = 0;
        $topics = $this->db->findTopics();
        foreach ($topics as $topic) {
            $newComments = [];
            $comments = $this->db->findCommentsOfTopic($topic);
            foreach ($comments as $comment) {
                if ($to == 'html') {
                    $message = Util::htmlify($this->view->esc($comment->message()));
                } else {
                    $message = Util::plainify($comment->message());
                }
                $newComments[] = $comment->withMessage($message);
                $count++;
            }
            $this->db->storeTopic($topic, $newComments);
        }
        $this->flashMessage->push($this->view->pmessage("success", "message_converted_$to", $count));
        return Response::redirect($request->url()->without("twocents_action")->absolute());
    }

    private function importComments(): Response
    {
        return Response::create($this->view->render("confirm", [
            "csrf_token" => $this->csrfProtector->token(),
            "message_key" => "message_topics_to_import",
            "count" => count($this->db->findTopics("txt")),
            "key" => "label_import_comments",
        ]))->withTitle("Twocents – " . $this->view->text("menu_main"));
    }

    private function doImportComments(Request $request): Response
    {
        $this->csrfProtector->check();
        $count = 0;
        $topics = $this->db->findTopics("txt");
        foreach ($topics as $topic) {
            $newComments = [];
            $comments = $this->db->findCommentsOfCommentsTopic($topic);
            foreach ($comments as $comment) {
                $message = $comment->message();
                if ($this->conf['comments_markup'] == 'HTML') {
                    $message = $this->htmlCleaner->clean($message);
                } else {
                    $message = Util::plainify($message);
                }
                $newComments[] = $comment->withMessage($message);
                $count++;
            }
            $this->db->storeTopic($topic, $newComments);
        }
        $this->flashMessage->push($this->view->pmessage("success", "message_imported_comments", $count));
        return Response::redirect($request->url()->without("twocents_action")->absolute());
    }

    private function importGbook(): Response
    {
        return Response::create($this->view->render("confirm", [
            "csrf_token" => $this->csrfProtector->token(),
            "message_key" => "message_topics_to_import",
            "count" => count($this->db->findTopics("txt")),
            "key" => "label_import_gbook",
        ]))->withTitle("Twocents – " . $this->view->text("menu_main"));
    }

    private function doImportGbook(Request $request): Response
    {
        $this->csrfProtector->check();
        $count = 0;
        $topics = $this->db->findTopics("txt");
        foreach ($topics as $topic) {
            $newComments = [];
            $oldComments = $this->db->findCommentsOfGbookTopic($topic);
            foreach ($oldComments as $comment) {
                $message = $comment->message();
                if ($this->conf['comments_markup'] == 'HTML') {
                    $message = $this->htmlCleaner->clean($message);
                } else {
                    $message = Util::plainify($message);
                }
                $newComments[] = $comment->withMessage($message);
                $count++;
            }
            $this->db->storeTopic($topic, $newComments);
        }
        $this->flashMessage->push($this->view->pmessage("success", "message_imported_gbook", $count));
        return Response::redirect($request->url()->without("twocents_action")->absolute());
    }
}
