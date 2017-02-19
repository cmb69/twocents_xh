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

class MainAdminController extends AbstractController
{
    /**
     * @var object
     */
    private $csrfProtector;

    public function __construct()
    {
        global $_XH_csrfProtection;

        $this->csrfProtector = $_XH_csrfProtection;
    }

    /**
     * @return string
     */
    public function defaultAction()
    {
        global $sn, $plugin_cf;

        $view = new View('admin');
        $view->action = "$sn?&twocents";
        $view->csrfTokenInput = new HtmlString($this->csrfProtector->tokenInput());
        if ($plugin_cf['twocents']['comments_markup'] == 'HTML') {
            $button = 'convert_plain';
        } else {
            $button = 'convert_html';
        }
        $view->buttons = array($button, 'import_comments', 'import_gbook');
        return $view->render();
    }

    /**
     * @return string
     */
    public function convertToHtmlAction()
    {
        return $this->convertTo('html');
    }

    /**
     * @return string
     */
    public function convertToPlainTextAction()
    {
        return $this->convertTo('plain');
    }

    /**
     * @param string $to A markup format ('html' or 'plain').
     * @return string
     */
    private function convertTo($to)
    {
        global $plugin_tx;

        $this->csrfProtector->check();
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
            }
        }
        $message = $plugin_tx['twocents']['message_converted_' . $to];
        return  XH_message('success', $message)
            . $this->defaultAction();
    }

    /**
     * @return string
     */
    public function importCommentsAction()
    {
        global $plugin_cf, $plugin_tx;

        $this->csrfProtector->check();
        $topics = CommentsTopic::findAll();
        foreach ($topics as $topic) {
            $comments = CommentsComment::findByTopicname($topic->getName());
            foreach ($comments as $comment) {
                $message = $comment->getMessage();
                if ($plugin_cf['twocents']['comments_markup'] == 'HTML') {
                    $message = $this->purify($message);
                } else {
                    $message = $this->plainify($message);
                }
                $comment->setMessage($message);
                $comment->insert();
            }
        }
        $message = $plugin_tx['twocents']['message_imported_comments'];
        return XH_message('success', $message)
            . $this->defaultAction();
    }

    /**
     * @return string
     * @todo Implement!
     */
    public function importGbookAction()
    {
        global $plugin_tx;

        $this->csrfProtector->check();
        return XH_message('info', $plugin_tx['twocents']['message_nyi'])
            . $this->defaultAction();
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
}
