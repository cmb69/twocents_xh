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
use Twocents\Value\HtmlString;

class MainAdminController
{
    /** @var string */
    private $scriptName;

    /** @var array<string,string> */
    private $conf;

    /** @var CsrfProtector|null */
    private $csrfProtector;

    /** @var Db */
    private $db;

    /** @var HtmlCleaner */
    private $htmlCleaner;

    /** @var View */
    private $view;

    /** @var HtmlString|null */
    private $message;

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

    public function defaultAction(): string
    {
        if ($this->conf['comments_markup'] == 'HTML') {
            $button = 'convert_to_plain_text';
        } else {
            $button = 'convert_to_html';
        }
        return $this->view->render('admin', [
            'action' => "{$this->scriptName}?&twocents",
            'csrfTokenInput' => $this->csrfProtector->token(),
            'buttons' => array($button, 'import_comments', 'import_gbook'),
            'message' => $this->message
        ]);
    }

    public function convertToHtmlAction(): string
    {
        return $this->convertTo('html');
    }

    public function convertToPlainTextAction(): string
    {
        return $this->convertTo('plain');
    }

    private function convertTo(string $to): string
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
        $this->message = new HtmlString($this->view->message('success', 'message_converted_' . $to, $count));
        return $this->defaultAction();
    }

    public function importCommentsAction(): string
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
        $this->message = new HtmlString($this->view->message('success', 'message_imported_comments', $count));
        return $this->defaultAction();
    }

    public function importGbookAction(): string
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
        $this->message = new HtmlString($this->view->message('success', 'message_imported_gbook', $count));
        return $this->defaultAction();
    }
}
