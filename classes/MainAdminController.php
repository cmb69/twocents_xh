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

use Twocents\Infra\CsrfProtector;
use Twocents\Infra\Db;
use Twocents\Infra\HtmlCleaner;
use Twocents\Infra\View;
use Twocents\Logic\Util;
use Twocents\Value\Response;

class MainAdminController
{
    /** @var string */
    private $scriptName;

    /** @var array<string,string> */
    private $conf;

    /** @var CsrfProtector */
    private $csrfProtector;

    /** @var Db */
    private $db;

    /** @var HtmlCleaner */
    private $htmlCleaner;

    /** @var View */
    private $view;

    /** @param array<string,string> $conf */
    public function __construct(
        string $scriptName,
        array $conf,
        CsrfProtector $csrfProtector,
        Db $db,
        HtmlCleaner $htmlCleaner,
        View $view
    ) {
        $this->scriptName = $scriptName;
        $this->conf = $conf;
        $this->csrfProtector = $csrfProtector;
        $this->db = $db;
        $this->htmlCleaner = $htmlCleaner;
        $this->view = $view;
    }

    public function __invoke(): Response
    {
        switch ($_POST["action"] ?? "") {
            default:
                return $this->defaultAction();
            case "convert_to_html":
                return $this->convertTo("html");
            case "convert_to_plain_text":
                return $this->convertTo("plain");
            case "import_comments":
                return $this->importCommentsAction();
            case "import_gbook":
                return $this->importGbookAction();
        }
    }

    private function defaultAction(): Response
    {
        if ($this->conf['comments_markup'] == 'HTML') {
            $button = 'convert_to_plain_text';
        } else {
            $button = 'convert_to_html';
        }
        return Response::create($this->view->render('admin', [
            'action' => "{$this->scriptName}?&twocents",
            'csrf_token' => $this->csrfProtector->token(),
            "buttons" => [
                ["value" => $button, "label" => "label_$button"],
                ["value" => "import_comments", "label" => "label_import_comments"],
                ["value" => "import_gbook", "label" => "label_import_gbook"],
            ],
        ]));
    }

    private function convertTo(string $to): Response
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
        return $this->defaultAction()->merge(Response::create(
            $this->view->message('success', 'message_converted_' . $to, $count)
        ));
    }

    private function importCommentsAction(): Response
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
        return $this->defaultAction()->merge(Response::create(
            $this->view->message('success', 'message_imported_comments', $count)
        ));
    }

    private function importGbookAction(): Response
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
        return $this->defaultAction()->merge(Response::create(
            $this->view->message('success', 'message_imported_gbook', $count)
        ));
    }
}
