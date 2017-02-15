<?php

/**
 * Copyright 2013-2014 The CMSimple_XH developers
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

/*
 * The environment variable CMSIMPLEDIR has to be set to the installation folder
 * (e.g. / or /cmsimple_xh/).
 *
 * A page "Twocents" with a Twocents_XH widget has to be there.
 */

namespace Twocents;

class CSRFAttackTest extends TestCase
{
    /**
     * @var string
     */
    protected $url;

    /**
     * @var resource
     */
    protected $curlHandle;

    /**
     * @var string
     */
    protected $cookieFile;

    public function setUp()
    {
        $this->url = 'http://localhost' . getenv('CMSIMPLEDIR');
        $this->cookieFile = tempnam(sys_get_temp_dir(), 'CC');

        $this->curlHandle = curl_init($this->url . '?&login=true&keycut=test');
        curl_setopt($this->curlHandle, CURLOPT_COOKIEJAR, $this->cookieFile);
        curl_setopt($this->curlHandle, CURLOPT_RETURNTRANSFER, true);
        curl_exec($this->curlHandle);
        curl_close($this->curlHandle);
    }

    /**
     * @param array $fields
     */
    protected function setCurlOptions($fields)
    {
        $options = array(
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $fields,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_COOKIEFILE => $this->cookieFile
        );
        curl_setopt_array($this->curlHandle, $options);
    }

    /**
     * @param array $fields
     * @param string $queryString
     * @dataProvider dataForAttack
     */
    public function testAttack($fields, $queryString = null)
    {
        $url = $this->url . (isset($queryString) ? '?' . $queryString : '');
        $this->curlHandle = curl_init($url);
        $this->setCurlOptions($fields);
        curl_exec($this->curlHandle);
        $actual = curl_getinfo($this->curlHandle, CURLINFO_HTTP_CODE);
        curl_close($this->curlHandle);
        $this->assertEquals(403, $actual);
    }

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
                    'action' => 'convert_html'
                ),
                '&twocents&normal'
            ),
            array(
                array(
                    'admin' => 'plugin_main',
                    'action' => 'convert_plain'
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
