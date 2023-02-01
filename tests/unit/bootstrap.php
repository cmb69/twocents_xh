<?php

/**
 * Copyright 2015-2017 Christoph M. Becker
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


require_once '../../cmsimple/functions.php';
require_once '../../cmsimple/adminfuncs.php';
if (file_exists('../../cmsimple/utf8.php')) {
    include_once '../../cmsimple/utf8.php';
} else {
    include_once '../utf8/utf8.php';
}
require_once '../../cmsimple/classes/CSRFProtection.php';
require_once '../pfw/classes/required_classes.php';
