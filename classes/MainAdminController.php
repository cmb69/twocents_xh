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

use XH\CSRFProtection as CsrfProtector;

class MainAdminController
{
    /** @var string */
    private $pluginFolder;

    /** @var string */
    private $scriptName;

    /** @var array<string,string> */
    private $conf;

    /** @var array<string,string> */
    private $lang;

    /** @var CsrfProtector|null */
    private $csrfProtector;

    /** @var HtmlString|null */
    private $message;

    /**
     * @param array<string,string> $conf
     * @param array<string,string> $lang
     * @param CsrfProtector|null $csrfProtector
     */
    public function __construct(
        string $pluginFolder,
        string $scriptName,
        array $conf,
        array $lang,
        $csrfProtector
    ) {
        $this->pluginFolder = $pluginFolder;
        $this->scriptName = $scriptName;
        $this->conf = $conf;
        $this->lang = $lang;
        $this->csrfProtector = $csrfProtector;
    }

    public function defaultAction(): string
    {
        if ($this->conf['comments_markup'] == 'HTML') {
            $button = 'convert_to_plain_text';
        } else {
            $button = 'convert_to_html';
        }
        $view = new View("{$this->pluginFolder}views/", $this->lang);
        return $view->render('admin', [
            'action' => "{$this->scriptName}?&twocents",
            'csrfTokenInput' => new HtmlString($this->csrfProtector->tokenInput()),
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
        $topics = Topic::findAll();
        foreach ($topics as $topic) {
            $comments = Comment::findByTopicname($topic->getName());
            foreach ($comments as $comment) {
                if ($to == 'html') {
                    $message = $this->htmlify(XH_hsc($comment->getMessage()));
                } else {
                    $message = $this->plainify($comment->getMessage());
                }
                $comment->setMessage($message);
                $comment->update();
                $count++;
            }
        }
        $this->message = new HtmlString(XH_message('success', $this->lang['message_converted_' . $to], $count));
        return $this->defaultAction();
    }

    private function htmlify(string $text): string
    {
        return preg_replace(
            array('/(?:\r\n|\r)/', '/\n{2,}/', '/\n/'),
            array("\n", '</p><p>', tag('br')),
            '<p>' . $text . '</p>'
        );
    }

    private function plainify(string $html): string
    {
        return html_entity_decode(
            strip_tags(
                str_replace(
                    array('</p><p>', tag('br')),
                    array(PHP_EOL . PHP_EOL, PHP_EOL),
                    $html
                )
            ),
            ENT_QUOTES,
            'UTF-8'
        );
    }

    public function importCommentsAction(): string
    {
        $this->csrfProtector->check();
        $count = 0;
        $topics = CommentsTopic::findAll();
        foreach ($topics as $topic) {
            $comments = CommentsComment::findByTopicname($topic->getName());
            foreach ($comments as $comment) {
                $message = $comment->getMessage();
                if ($this->conf['comments_markup'] == 'HTML') {
                    $htmlCleaner = new HtmlCleaner($this->pluginFolder, false);
                    $message = $htmlCleaner->clean($message);
                } else {
                    $message = $this->plainify($message);
                }
                $comment->setMessage($message);
                $comment->insert(uniqid());
                $count++;
            }
        }
        $this->message = new HtmlString(XH_message('success', $this->lang['message_imported_comments'], $count));
        return $this->defaultAction();
    }

    /** @todo Implement! */
    public function importGbookAction(): string
    {
        $this->csrfProtector->check();
        $count = 0;
        $topics = CommentsTopic::findAll();
        foreach ($topics as $topic) {
            $comments = GbookComment::findByTopicname($topic->getName());
            foreach ($comments as $comment) {
                $message = $comment->getMessage();
                if ($this->conf['comments_markup'] == 'HTML') {
                    $htmlCleaner = new HtmlCleaner($this->pluginFolder, false);
                    $message = $htmlCleaner->clean($message);
                } else {
                    $message = $this->plainify($message);
                }
                $comment->setMessage($message);
                $comment->insert(uniqid());
                $count++;
            }
        }
        $this->message = new HtmlString(XH_message('success', $this->lang['message_imported_gbook'], $count));
        return $this->defaultAction();
    }
}
