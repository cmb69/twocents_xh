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

class CommentView extends View
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
        $id = $this->isCurrentComment()
            ? ''
            : ' id="twocents_comment_' . $this->comment->getId() . '"';
        $class = $this->comment->isVisible() ? '' : ' twocents_hidden';
        $html = '<div' . $id . ' class="twocents_comment' . $class . '">';
        if ($this->isCurrentComment()) {
            $view = new CommentFormView($this->currentComment);
            $html .= $view->render();
        } else {
            if (XH_ADM) {
                $html .= $this->renderAdminTools();
            }
            $html .= '<div class="twocents_attribution">'
                . $this->renderAttribution() . '</div>'
                . '<div class="twocents_message">' . $this->renderMessage()
                . '</div>';
        }
        $html .= '</div>';
        return $html;
    }

    /**
     * @return string
     */
    protected function renderAdminTools()
    {
        return '<div class="twocents_admin_tools">'
            . $this->renderEditLink()
            . $this->renderDeleteForm()
            . '</div>';
    }

    /**
     * @return string
     */
    protected function renderDeleteForm()
    {
        global $plugin_tx, $_XH_csrfProtection;

        $hideLabel = $this->comment->isVisible()
            ? $plugin_tx['twocents']['label_hide']
            : $plugin_tx['twocents']['label_show'];
        return '<form method="post" action="' . XH_hsc($this->getUrl()) . '">'
            . $_XH_csrfProtection->tokenInput()
            . tag(
                'input type="hidden" name="twocents_id" value="'
                . $this->comment->getId() . '"'
            )
            . '<button type="submit" name="twocents_action"'
            . ' value="toggle_visibility">' . $hideLabel . '</button>'
            . '<button type="submit" name="twocents_action" value="remove_comment">'
            . $plugin_tx['twocents']['label_delete'] . '</button>'
            . '</form>';
    }

    /**
     * @return string
     */
    protected function renderEditLink()
    {
        global $plugin_tx;

        $url = $this->getUrl() . '&twocents_id=' . $this->comment->getId();
        return '<a href="' . XH_hsc($url) . '">'
            . $plugin_tx['twocents']['label_edit'] . '</a>';
    }

    /**
     * @return string
     */
    protected function renderAttribution()
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
    protected function renderMessage()
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
    protected function isCurrentComment()
    {
        return isset($this->currentComment)
            && $this->currentComment->getId() == $this->comment->getId();
    }
}
