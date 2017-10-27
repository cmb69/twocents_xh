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

class MainAdminController extends Controller
{
    /**
     * @var ?HtmlString
     */
    private $message;

    public function defaultAction()
    {
        $view = new View('admin');
        $view->action = "{$this->scriptName}?&twocents";
        $view->csrfTokenInput = new HtmlString($this->csrfProtector->tokenInput());
        if ($this->config['comments_markup'] == 'HTML') {
            $button = 'convert_to_plain_text';
        } else {
            $button = 'convert_to_html';
        }
        $view->buttons = array($button, 'import_comments', 'import_gbook');
        $view->message = $this->message;
        $view->render();
    }

    public function convertToHtmlAction()
    {
        $this->convertTo('html');
    }

    public function convertToPlainTextAction()
    {
        $this->convertTo('plain');
    }

    /**
     * @param string $to
     */
    private function convertTo($to)
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
        $this->defaultAction();
    }

    /**
     * @param string $text
     * @return string
     */
    private function htmlify($text)
    {
        return preg_replace(
            array('/(?:\r\n|\r)/', '/\n{2,}/', '/\n/'),
            array("\n", '</p><p>', tag('br')),
            '<p>' . $text . '</p>'
        );
    }

    /**
     * @param string $html
     * @return string
     */
    private function plainify($html)
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

    public function importCommentsAction()
    {
        $this->csrfProtector->check();
        $count = 0;
        $topics = CommentsTopic::findAll();
        foreach ($topics as $topic) {
            $comments = CommentsComment::findByTopicname($topic->getName());
            foreach ($comments as $comment) {
                $message = $comment->getMessage();
                if ($this->config['comments_markup'] == 'HTML') {
                    $message = $this->purify($message);
                } else {
                    $message = $this->plainify($message);
                }
                $comment->setMessage($message);
                $comment->insert();
                $count++;
            }
        }
        $this->message = new HtmlString(XH_message('success', $this->lang['message_imported_comments'], $count));
        $this->defaultAction();
    }

    /**
     * @todo Implement!
     */
    public function importGbookAction()
    {
        $this->csrfProtector->check();
        $count = 0;
        $topics = CommentsTopic::findAll();
        foreach ($topics as $topic) {
            $comments = GbookComment::findByTopicname($topic->getName());
            foreach ($comments as $comment) {
                $message = $comment->getMessage();
                if ($this->config['comments_markup'] == 'HTML') {
                    $message = $this->purify($message);
                } else {
                    $message = $this->plainify($message);
                }
                $comment->setMessage($message);
                $comment->insert();
                $count++;
            }
        }
        $this->message = new HtmlString(XH_message('success', $this->lang['message_imported_gbook'], $count));
        $this->defaultAction();
    }
}
