<?php

/**
 * Copyright (c) Christoph M. Becker
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

namespace Twocents\Infra;

use HTMLPurifier;
use HTMLPurifier_Config;

class HtmlCleaner
{
    /** @var string */
    private $pluginFolder;

    /** @var HTMLPurifier|null */
    private $purifier = null;

    public function __construct(string $pluginFolder)
    {
        $this->pluginFolder = $pluginFolder;
    }

    /** @return void */
    private function init()
    {
        if ($this->purifier !== null) {
            return;
        }
        include_once "{$this->pluginFolder}htmlpurifier/HTMLPurifier.standalone.php";
        $config = HTMLPurifier_Config::createDefault();
        $config->set('HTML.Doctype', 'HTML 4.01 Transitional');
        $config->set('HTML.Allowed', 'p,blockquote,br,b,strong,i,em,a[href]');
        $config->set('AutoFormat.AutoParagraph', true);
        $config->set('AutoFormat.RemoveEmpty', true);
        $config->set('AutoFormat.RemoveEmpty.RemoveNbsp', true);
        $config->set('Cache.SerializerPath', "{$this->pluginFolder}cache");
        $config->set('HTML.Nofollow', true);
        $config->set('Output.TidyFormat', true);
        $config->set('Output.Newline', "\n");
        $this->purifier = new HTMLPurifier($config);
    }

    public function clean(string $message): string
    {
        $this->init();
        $message = str_replace(array('&nbsp;', "\C2\A0"), ' ', $message);
        return $this->purifier->purify($message);
    }
}
