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
        global $_XH_csrfProtection;

        $view = new View('comment-form');
        $view->action = $this->comment->getId() ? 'update' : 'add';
        $view->url = $this->getUrl();
        $view->comment = $this->comment;
        $view->captcha = new HtmlString($this->renderCaptcha());
        if ($this->comment->getId()) {
            $view->csrfTokenInput = new HtmlString($_XH_csrfProtection->tokenInput());
        } else {
            $view->csrfTokenInput = '';
        }
        return $view->render();
    }

    /**
     * @return string
     */
    private function renderCaptcha()
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
}
