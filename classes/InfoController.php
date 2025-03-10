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

use Twocents\Infra\Db;
use Twocents\Infra\SystemChecker;
use Twocents\Infra\View;
use Twocents\Value\Response;

class InfoController
{
    /** @var string */
    private $pluginFolder;

    /** @var SystemChecker */
    private $systemChecker;

    /** @var Db */
    private $db;

    /** @var View */
    private $view;

    public function __construct(string $pluginFolder, SystemChecker $systemChecker, Db $db, View $view)
    {
        $this->pluginFolder = $pluginFolder;
        $this->systemChecker = $systemChecker;
        $this->db = $db;
        $this->view = $view;
    }

    public function __invoke(): Response
    {
        return Response::create($this->view->render("info", [
            "version" => TWOCENTS_VERSION,
            "checks" => $this->getChecks(),
        ]))->withTitle("Twocents " . TWOCENTS_VERSION);
    }

    /** @return list<array{key:string,arg:string,class:string,state:string}> */
    private function getChecks()
    {
        return array(
            $this->checkPhpVersion('7.1.0'),
            $this->checkExtension('json'),
            $this->checkXhVersion('1.7.0'),
            $this->checkWritability($this->db->getFoldername()),
            $this->checkWritability($this->pluginFolder . "config/"),
            $this->checkWritability($this->pluginFolder . "css/"),
            $this->checkWritability($this->pluginFolder . "languages/"),
        );
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
    private function checkExtension(string $extension)
    {
        $state = $this->systemChecker->checkExtension($extension) ? 'success' : 'fail';
        return [
            "key" => "syscheck_extension",
            "arg" => $extension,
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
