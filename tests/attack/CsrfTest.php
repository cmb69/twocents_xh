<?php

/*
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

/*
 * A page "Twocents" with a Twocents_XH widget has to be there.
 */

class CsrfTest extends CsrfTestCase
{
    /**
     * @return array
     */
    public function dataForAttack()
    {
        return array(
            array(
                array(
                    'twocents_id' => '53d8e06e34a34',
                    'twocents_user' => 'hacker',
                    'twocents_email' => 'hacker@example.com',
                    'twocents_message' => 'hacked!',
                    'twocents_action' => 'update_comment'
                ),
                'Twocents&normal'
            ),
            array(
                array(
                    'twocents_id' => '53d8e06e34a34',
                    'twocents_action' => 'toggle_visibility'
                ),
                'Twocents&normal'
            ),
            array(
                array(
                    'twocents_id' => '53d8e06e34a34',
                    'twocents_action' => 'remove_comment'
                ),
                'Twocents&normal'
            ),
            array(
                array(
                    'admin' => 'plugin_main',
                    'action' => 'convert_to_html'
                ),
                '&twocents&normal'
            ),
            array(
                array(
                    'admin' => 'plugin_main',
                    'action' => 'import_comments'
                ),
                '&twocents&normal'
            ),
            array(
                array(
                    'admin' => 'plugin_main',
                    'action' => 'import_gbook'
                ),
                '&twocents&normal'
            )
        );
    }
}
