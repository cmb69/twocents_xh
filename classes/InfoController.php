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

use Plib\DocumentStore;
use Plib\Response;
use Plib\SystemChecker;
use Plib\View;

class InfoController
{
    /** @var string */
    private $pluginFolder;

    /** @var array<string,string> */
    private $conf;

    /** @var SystemChecker */
    private $systemChecker;

    /** @var DocumentStore */
    private $store;

    /** @var View */
    private $view;

    /** @param array<string,string> $conf */
    public function __construct(
        string $pluginFolder,
        array $conf,
        SystemChecker $systemChecker,
        DocumentStore $store,
        View $view
    ) {
        $this->pluginFolder = $pluginFolder;
        $this->conf = $conf;
        $this->systemChecker = $systemChecker;
        $this->store = $store;
        $this->view = $view;
    }

    public function __invoke(): Response
    {
        return Response::create($this->view->render("info", [
            "version" => Dic::VERSION,
            "checks" => $this->getChecks(),
        ]))->withTitle("Twocents " . Dic::VERSION);
    }

    /** @return list<array{key:string,arg:string,class:string,state:string}> */
    private function getChecks()
    {
        return array_filter([
            $this->checkPhpVersion('7.1.0'),
            $this->checkXhVersion('1.7.0'),
            $this->checkPlibVersion("1.8"),
            $this->checkPhpmailerVersion("6.9.3"),
            $this->checkWritability($this->store->folder()),
            $this->checkWritability($this->pluginFolder . "config/"),
            $this->checkWritability($this->pluginFolder . "css/"),
            $this->checkWritability($this->pluginFolder . "languages/"),
        ]);
    }

    /** @return array{key:string,arg:string,class:string,state:string} */
    private function checkPhpVersion(string $version)
    {
        $state = $this->systemChecker->checkVersion(PHP_VERSION, $version) ? 'success' : 'fail';
        return [
            "key" => "syscheck_phpversion",
            "arg" => $version,
            "class" => "xh_$state",
            "state" => "syscheck_$state",
        ];
    }

    /** @return array{key:string,arg:string,class:string,state:string} */
    private function checkXhVersion(string $version)
    {
        $state = $this->systemChecker->checkVersion(CMSIMPLE_XH_VERSION, "CMSimple_XH $version") ? 'success' : 'fail';
        return [
            "key" => "syscheck_xhversion",
            "arg" => $version,
            "class" => "xh_$state",
            "state" => "syscheck_$state",
        ];
    }

    /** @return array{key:string,arg:string,class:string,state:string} */
    private function checkPlibVersion(string $version)
    {
        $state = $this->systemChecker->checkPlugin("plib", $version) ? 'success' : 'fail';
        return [
            "key" => "syscheck_plibversion",
            "arg" => $version,
            "class" => "xh_$state",
            "state" => "syscheck_$state",
        ];
    }

    /** @return ?array{key:string,arg:string,class:string,state:string} */
    private function checkPhpmailerVersion(string $version): ?array
    {
        if (!$this->conf["email_address"]) {
            return null;
        }
        $state = $this->systemChecker->checkPlugin("phpmailer", $version) ? 'success' : 'fail';
        return [
            "key" => "syscheck_phpmailerversion",
            "arg" => $version,
            "class" => "xh_$state",
            "state" => "syscheck_$state",
        ];
    }

    /** @return array{key:string,arg:string,class:string,state:string} */
    private function checkWritability(string $folder)
    {
        $state = $this->systemChecker->checkWritability($folder) ? 'success' : 'warning';
        return [
            "key" => "syscheck_writable",
            "arg" => $folder,
            "class" => "xh_$state",
            "state" => "syscheck_$state",
        ];
    }
}
