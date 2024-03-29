<?php

/**
 * Copyright 2017-2023 Christoph M. Becker
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

namespace Twocents\Logic;

class SpamFilter
{
    /** @var string */
    private $spamWords;

    public function __construct(string $spamWords)
    {
        $this->spamWords = $spamWords;
    }

    public function isSpam(string $message): bool
    {
        $words = array_map(
            function ($word) {
                return preg_quote(trim($word), '/');
            },
            explode(',', $this->spamWords)
        );
        $pattern = implode('|', $words);
        return (bool) preg_match("/$pattern/ui", $message);
    }
}
