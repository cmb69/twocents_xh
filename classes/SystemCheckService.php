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

namespace Twocents;

class SystemCheckService
{
    /**
     * @var string
     */
    private $pluginsFolder;

    /**
     * @var string
     */
    private $pluginFolder;

    /**
     * @var array<string,string>
     */
    private $lang;

    /**
     * @var string
     */
    private $dataFolder;

    /** @var SystemChecker */
    private $systemChecker;

    /**
     * @param array<string,string> $lang
     */
    public function __construct(string $pluginsFolder, array $lang, string $dataFolder, SystemChecker $systemChecker)
    {
        $this->pluginsFolder = $pluginsFolder;
        $this->pluginFolder = "{$this->pluginsFolder}twocents";
        $this->lang = $lang;
        $this->dataFolder = $dataFolder;
        $this->systemChecker = $systemChecker;
    }

    /**
     * @return array<int,array{state:string,label:string,stateLabel:string}>
     */
    public function getChecks()
    {
        return array(
            $this->checkPhpVersion('7.0.0'),
            $this->checkExtension('json'),
            $this->checkXhVersion('1.7.0'),
            $this->checkWritability($this->dataFolder),
            $this->checkWritability("$this->pluginFolder/config/"),
            $this->checkWritability("$this->pluginFolder/css/"),
            $this->checkWritability("$this->pluginFolder/languages/"),
        );
    }

    /**
     * @return array{state:string,label:string,stateLabel:string}
     */
    private function checkPhpVersion(string $version)
    {
        $state = $this->systemChecker->checkVersion(PHP_VERSION, $version) ? 'success' : 'fail';
        $label = sprintf($this->lang['syscheck_phpversion'], $version);
        $stateLabel = $this->lang["syscheck_$state"];
        return compact('state', 'label', 'stateLabel');
    }

    /**
     * @return array{state:string,label:string,stateLabel:string}
     */
    private function checkExtension(string $extension, bool $isMandatory = true)
    {
        $state = $this->systemChecker->checkExtension($extension) ? 'success' : ($isMandatory ? 'fail' : 'warning');
        $label = sprintf($this->lang['syscheck_extension'], $extension);
        $stateLabel = $this->lang["syscheck_$state"];
        return compact('state', 'label', 'stateLabel');
    }

    /**
     * @return array{state:string,label:string,stateLabel:string}
     */
    private function checkXhVersion(string $version)
    {
        $state = $this->systemChecker->checkVersion(CMSIMPLE_XH_VERSION, "CMSimple_XH $version") ? 'success' : 'fail';
        $label = sprintf($this->lang['syscheck_xhversion'], $version);
        $stateLabel = $this->lang["syscheck_$state"];
        return compact('state', 'label', 'stateLabel');
    }

    /**
     * @return array{state:string,label:string,stateLabel:string}
     */
    private function checkWritability(string $folder)
    {
        $state = $this->systemChecker->checkWritability($folder) ? 'success' : 'warning';
        $label = sprintf($this->lang['syscheck_writable'], $folder);
        $stateLabel = $this->lang["syscheck_$state"];
        return compact('state', 'label', 'stateLabel');
    }
}
