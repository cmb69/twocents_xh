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

class CommentFormView extends AbstractController
{
    /**
     * @var Comment
     */
    protected $comment;

    public function __construct(Comment $comment = null)
    {
        if (isset($comment)) {
            $this->comment = $comment;
        } else {
            $this->comment = Comment::make(null, null);
        }
    }

    /**
     * @return string
     */
    public function render()
    {
        $url = XH_hsc($this->getUrl());
        return '<form class="twocents_form" method="post" action="' . $url . '">'
            . $this->renderHiddenFormFields()
            . $this->renderUserInput()
            . $this->renderEmailInput()
            . $this->renderMessageTextarea()
            . $this->renderCaptcha()
            . $this->renderButtons()
            . '</form>';
    }

    /**
     * @return string
     */
    protected function renderHiddenFormFields()
    {
        global $_XH_csrfProtection;

        $html = '';
        if ($this->comment->getId()) {
            $html .= $_XH_csrfProtection->tokenInput();
        }
        $html .= tag(
            'input type="hidden" name="twocents_id" value="'
            . XH_hsc($this->comment->getId()) . '"'
        );
        return $html;
    }

    /**
     * @return string
     */
    protected function renderUserInput()
    {
        global $plugin_tx;

        return '<div><label><span>' . $plugin_tx['twocents']['label_user']
            . '</span>'
            . tag(
                'input type="text" name="twocents_user" value="'
                . XH_hsc($this->comment->getUser())
                . '" size="20" required="required"'
            )
            . '</label></div>';
    }

    /**
     * @return string
     */
    protected function renderEmailInput()
    {
        global $plugin_tx;

        return '<div><label><span>' . $plugin_tx['twocents']['label_email']
            . '</span>'
            . tag(
                'input type="email" name="twocents_email" value="'
                . XH_hsc($this->comment->getEmail())
                . '" size="20" required="required"'
            )
            . '</label></div>';
    }

    /**
     * @return string
     */
    protected function renderMessageTextarea()
    {
        global $plugin_tx;

        return '<div><label><span>' . $plugin_tx['twocents']['label_message']
            . '</span>'
            . '<textarea name="twocents_message" cols="50" rows="8"'
            . ' required="required">'
            . XH_hsc($this->comment->getMessage()) . '</textarea></label></div>';
    }

    /**
     * @return string
     */
    protected function renderCaptcha()
    {
        global $pth, $plugin_cf;

        $pluginname = $plugin_cf['twocents']['captcha_plugin'];
        $filename = $pth['folder']['plugins'] . $pluginname . '/captcha.php';
        if (!XH_ADM && $pluginname && is_readable($filename)) {
            include_once $filename;
            return call_user_func($pluginname . '_captcha_display');
        } else {
            return '';
        }
    }

    /**
     * @return string
     */
    protected function renderButtons()
    {
        global $plugin_tx;

        $ptx = $plugin_tx['twocents'];
        $action = $this->comment->getId() ? 'update' : 'add';
        $html = '<div class="twocents_form_buttons">'
            . '<button type="submit" name="twocents_action" value="' . $action
            . '_comment">' . $ptx['label_' . $action] . '</button>';
        if ($this->comment->getId()) {
            $html .= '<a href="' . $this->getUrl() . '">'
                . $ptx['label_cancel'] . '</a>';
        }
        $html .= '<button type="reset">' . $ptx['label_reset'] . '</button>'
            . '</div>';
        return $html;
    }
}
