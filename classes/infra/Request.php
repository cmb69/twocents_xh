<?php

/**
 * Copyright 2023 Christoph M. Becker
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

class Request
{
    /** @codeCoverageIgnore */
    public function admin(): bool
    {
        return defined('XH_ADM') && XH_ADM;
    }

    public function time(): int
    {
        return (int) $this->server()["REQUEST_TIME"];
    }

    public function pluginsFolder(): string
    {
        return $this->pth()["folder"]["plugins"];
    }

    /**
     * @codeCoverageIgnore
     * @return array<string,string>
     */
    protected function server(): array
    {
        return $_SERVER;
    }

    /** @return array{folder:array<string,string>,file:array<string,string>} */
    protected function pth(): array
    {
        global $pth;
        return $pth;
    }
}
