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

class CommentView extends AbstractController
{
    /**
     * @var Comment
     */
    protected $comment;

    /**
     * @var ?Comment
     */
    protected $currentComment;

    public function __construct(Comment $comment, Comment $currentComment = null)
    {
        $this->comment = $comment;
        $this->currentComment = $currentComment;
    }

    /**
     * @return string
     */
    public function render()
    {
        global $_XH_csrfProtection;

        $view = new View('comment');
        $isCurrentComment = $this->isCurrentComment();
        $view->id = 'twocents_comment_' . $this->comment->getId();
        $view->className = $this->comment->isVisible() ? '' : ' twocents_hidden';
        $view->isCurrentComment = $isCurrentComment;
        if ($isCurrentComment) {
            $formView = new CommentFormView($this->currentComment);
            $view->form = new HtmlString($formView->render());
        } else {
            $view->isAdmin = XH_ADM;
            $view->url = $this->getUrl();
            $view->editUrl = $this->getUrl() . '&twocents_id=' . $this->comment->getId();
            $view->comment = $this->comment;
            if (XH_ADM) {
                $view->csrfTokenInput = new HtmlString($_XH_csrfProtection->tokenInput());
            }
            $view->visibility = $this->comment->isVisible() ? 'label_hide' : 'label_show';
            $view->attribution = new HtmlString($this->renderAttribution());
            $view->message = new HtmlString($this->renderMessage());
        }
        return $view->render();
    }

    /**
     * @return string
     */
    private function renderAttribution()
    {
        global $plugin_tx;

        $date = date($plugin_tx['twocents']['format_date'], $this->comment->getTime());
        $time = date($plugin_tx['twocents']['format_time'], $this->comment->getTime());
        return strtr(
            $plugin_tx['twocents']['format_heading'],
            array(
                '{DATE}' => $date,
                '{TIME}' => $time,
                '{USER}' => XH_hsc($this->comment->getUser())
            )
        );
    }

    /**
     * @return string
     */
    private function renderMessage()
    {
        global $plugin_cf;

        if ($plugin_cf['twocents']['comments_markup'] == 'HTML') {
            return $this->comment->getMessage();
        } else {
            return preg_replace('/(?:\r\n|\r|\n)/', tag('br'), XH_hsc($this->comment->getMessage()));
        }
    }

    /**
     * @return bool
     */
    private function isCurrentComment()
    {
        return isset($this->currentComment)
            && $this->currentComment->getId() == $this->comment->getId();
    }
}
