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

use Plib\CsrfProtector;
use Plib\DocumentStore;
use Plib\Request;
use Plib\Response;
use Plib\View;
use Twocents\Infra\FlashMessage;
use Twocents\Infra\HtmlCleaner;
use Twocents\Logic\Util;
use Twocents\Model\Topic;

class MainAdminController
{
    /** @var array<string,string> */
    private $conf;

    /** @var CsrfProtector */
    private $csrfProtector;

    /** @var DocumentStore */
    private $store;

    /** @var FlashMessage */
    private $flashMessage;

    /** @var View */
    private $view;

    /** @param array<string,string> $conf */
    public function __construct(
        array $conf,
        CsrfProtector $csrfProtector,
        DocumentStore $store,
        FlashMessage $flashMessage,
        View $view
    ) {
        $this->conf = $conf;
        $this->csrfProtector = $csrfProtector;
        $this->store = $store;
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
            "flash_message" => $this->flashMessage->pop(),
            "buttons" => [
                ["value" => $button, "label" => "label_$button"],
            ],
        ]))->withTitle("Twocents – " . $this->view->text("menu_main"));
    }

    private function convertTo(string $to): Response
    {
        return Response::create($this->view->render("confirm", [
            "csrf_token" => $this->csrfProtector->token(),
            "message_key" => "message_topics_to_convert",
            "count" => count(Topic::all($this->store)),
            "key" => $to === "html" ? "label_convert_to_html" : "label_convert_to_plain_text",
        ]))->withTitle("Twocents – " . $this->view->text("menu_main"));
    }

    private function doConvertTo(Request $request, string $to): Response
    {
        if (!$this->csrfProtector->check($request->post("twocents_token"))) {
            return Response::create($this->view->message("fail", "error_unauthorized"));
        }
        $count = 0;
        $topics = Topic::all($this->store);
        foreach ($topics as $topicname) {
            $topic = Topic::update($topicname, $this->store);
            foreach ($topic->comments() as $comment) {
                if ($to == 'html') {
                    $message = Util::htmlify($this->view->esc($comment->message()));
                } else {
                    $message = Util::plainify($comment->message());
                }
                $topic->updateComment($comment->withMessage($message));
                $count++;
            }
        }
        $this->store->commit(); // TODO handle failure
        $this->flashMessage->push($this->view->pmessage("success", "message_converted_$to", $count));
        return Response::redirect($request->url()->without("twocents_action")->absolute());
    }
}
