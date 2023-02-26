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

use Realblog\CommentsBridge;
use Twocents\Infra\Db;

class RealblogBridge implements CommentsBridge
{
    /**
     * @param string $topic
     * @return int
     */
    public static function count($topic)
    {
        return count(Db::findTopic($topic, true));
    }

    /**
     * @param string $topic
     * @return string
     */
    public static function handle($topic)
    {
        global $pth, $plugin_cf, $plugin_tx, $_XH_csrfProtection;

        $controller = new MainController(
            $pth['folder']['plugins'],
            $plugin_cf['twocents'],
            $plugin_tx['twocents'],
            isset($_XH_csrfProtection) ? $_XH_csrfProtection : null,
            $topic,
            false
        );
        $action = Plugin::getControllerAction($controller, 'twocents_action');
        ob_start();
        $controller->{$action}();
        $comments = ob_get_clean();
        return '<div class="twocents_realblog_comments">'
            . '<' . $plugin_cf['twocents']['realblog_heading'] . '>'
            .  $plugin_tx['twocents']['realblog_heading']
            . '</' . $plugin_cf['twocents']['realblog_heading'] . '>'
            . $comments
            . '</div>';
    }

    /**
     * @param string $topic
     * @return string|false
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public static function getEditUrl($topic)
    {
        return false;
    }
}
