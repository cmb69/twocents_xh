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

class CommentsView
{
    /**
     * @param Comment[] $comments
     * @param string $messages
     * @return CommentsView
     */
    public static function make(array $comments, Comment $currentComment = null, $messages = '')
    {
        return new self($comments, $currentComment, $messages);
    }

    /**
     * @var Comment[]
     */
    protected $comments;

    /**
     * @var ?Comment
     */
    protected $currentComment;

    /**
     * @var string
     */
    protected $messages;

    /**
     * @param Comment[] $comments
     * @param string $messages
     */
    protected function __construct(array $comments, Comment $currentComment = null, $messages = '')
    {
        $this->comments = (array) $comments;
        $this->currentComment = $currentComment;
        $this->messages = (string) $messages;
    }

    /**
     * @return string
     */
    public function render()
    {
        $this->writeScriptsToBjs();
        $html = '<div class="twocents_comments">';
        foreach ($this->comments as $comment) {
            if ($comment->isVisible() || XH_ADM) {
                if (isset($this->currentComment)
                    && $this->currentComment->getId() == $comment->getId()
                ) {
                    $html .= $this->messages;
                }
                $view = new CommentView($comment, $this->currentComment);
                $html .= $view->render();
            }
        }
        $html .= '</div>';
        if (!isset($this->currentComment)
            || $this->currentComment->getId() == null
        ) {
            $view = new CommentFormView($this->currentComment);
            $html .= $this->messages . $view->render();
        }
        return $html;
    }

    protected function writeScriptsToBjs()
    {
        global $pth, $bjs, $plugin_cf, $plugin_tx;

        $config = array();
        foreach (array('comments_markup') as $property) {
            $config[$property] = $plugin_cf['twocents'][$property];
        }
        foreach (array('label_new', 'message_delete') as $property) {
            $config[$property] = $plugin_tx['twocents'][$property];
        }
        $json = XH_encodeJson($config);
        $filename = $pth['folder']['plugins'] . 'twocents/twocents.js';
        $bjs .= <<<EOT
<script type="text/javascript">/* <[CDATA[ */var TWOCENTS = $json;/* ]]> */</script>
<script type="text/javascript" src="$filename"></script>
EOT;
    }
}
