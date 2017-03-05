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

use HTMLPurifier;
use HTMLPurifier_Config;

abstract class Controller
{
    /**
     * @var string
     */
    protected $scriptName;

    /**
     * @var array
     */
    protected $config;

    /**
     * @var array
     */
    protected $lang;

    /**
     * @var object
     */
    protected $csrfProtector;

    /**
     * @var bool
     */
    private $isXhtml;

    public function __construct()
    {
        global $sn, $cf, $plugin_cf, $plugin_tx, $_XH_csrfProtection;

        $this->scriptName = $sn;
        $this->config = $plugin_cf['twocents'];
        $this->lang = $plugin_tx['twocents'];
        if (isset($_XH_csrfProtection)) {
            $this->csrfProtector = $_XH_csrfProtection;
        }
        $this->isXhtml = (bool) $cf['xhtml']['endtags'];
    }

    /**
     * @param string $message
     * @return string
     */
    protected function purify($message)
    {
        include_once "{$this->pluginsFolder}twocents/htmlpurifier/HTMLPurifier.standalone.php";
        $config = HTMLPurifier_Config::createDefault();
        if (!$this->isXhtml) {
            $config->set('HTML.Doctype', 'HTML 4.01 Transitional');
        }
        $config->set('HTML.Allowed', 'p,blockquote,br,b,strong,i,em,a[href]');
        $config->set('AutoFormat.AutoParagraph', true);
        $config->set('AutoFormat.RemoveEmpty', true);
        $config->set('AutoFormat.RemoveEmpty.RemoveNbsp', true);
        $config->set('Cache.SerializerPath', "{$this->pluginsFolder}twocents/cache");
        $config->set('HTML.Nofollow', true);
        $config->set('Output.TidyFormat', true);
        $config->set('Output.Newline', "\n");
        $purifier = new HTMLPurifier($config);
        $message = str_replace(array('&nbsp;', "\C2\A0"), ' ', $message);
        return $purifier->purify($message);
    }
}
