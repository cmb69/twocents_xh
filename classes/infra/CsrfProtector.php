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

use Exception;
use XH\CSRFProtection;

class CsrfProtector
{
    public function token(): string
    {
        $html = $this->cSRFProtection()->tokenInput();
        if (preg_match('/value="([0-9a-f]+)"/', $html, $matches)) {
            return $matches[1];
        }
        throw new Exception("CSRFProtection won't work");
    }

    /** @return void */
    public function check()
    {
        $this->cSRFProtection()->check();
    }

    /** @codeCoverageIgnore */
    protected function cSRFProtection(): CSRFProtection
    {
        global $_XH_csrfProtection;

        return $_XH_csrfProtection;
    }
}
