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

use XH\CSRFProtection;

class FakeCsrfProtector extends CsrfProtector
{
    private $hasChecked = false;

    public function hasChecked(): bool
    {
        return $this->hasChecked;
    }

    public function check()
    {
        parent::check();
        $this->hasChecked = true;
    }

    protected function cSRFProtection(): CSRFProtection
    {
        static $instance;

        if (!isset($instance)) {
            return new class($this->hasChecked) extends CSRFProtection {
                public function __construct() {}
                public function tokenInput()
                {
                    return "<input type=\"hidden\" name=\"xh_csrf_token\" value=\"e3c1b42a6098b48a39f9f54ddb3388f7\">";
                }
                public function check() {}
            };
        }
        return $instance;
    }
}
