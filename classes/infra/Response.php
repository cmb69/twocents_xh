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

class Response
{
    public static function create(string $output): self
    {
        $that = new Response;
        $that->output = $output;
        return $that;
    }

    public static function createContentType(string $contentType): self
    {
        $that = new Response;
        $that->contentType = $contentType;
        return $that;
    }

    public static function createRedirect(string $location): self
    {
        $that = new Response;
        $that->location = $location;
        return $that;
    }

    /** @var string */
    private $output = "";

    /** @var string|null */
    private $hjs = null;

    /** @var string|null */
    private $bjs = null;

    /** @var string|null */
    private $contentType = null;

    /** @var string|null */
    private $location = null;

    public function setOutput(string $output): self
    {
        $this->output = $output;
        return $this;
    }

    public function setHjs(string $hjs): self
    {
        $this->hjs = $hjs;
        return $this;
    }

    public function setBjs(string $bjs): self
    {
        $this->bjs = $bjs;
        return $this;
    }

    public function merge(Response $other): self
    {
        assert($this->contentType === null);
        assert($this->location === null);
        $this->output .= $other->output;
        return $this;
    }

    public function output(): string
    {
        return $this->output;
    }

    public function hjs(): ?string
    {
        return $this->hjs;
    }

    public function bjs(): ?string
    {
        return $this->bjs;
    }

    public function contentType(): ?string
    {
        return $this->contentType;
    }

    public function location(): ?string
    {
        return $this->location;
    }

    /** @return string|never */
    public function fire()
    {
        global $hjs, $bjs;

        if ($this->contentType !== null) {
            while (ob_get_level()) {
                ob_end_clean();
            }
            header("Content-Type: {$this->contentType}");
            echo $this->output;
            exit;
        }
        if ($this->location !== null) {
            while (ob_get_level()) {
                ob_end_clean();
            }
            header("Location: {$this->location}", true, 303);
            echo $this->output;
            exit;
        }
        if ($this->hjs !== null) {
            $hjs .= $this->hjs;
        }
        if ($this->bjs !== null) {
            $bjs .= $this->bjs;
        }
        return $this->output;
    }
}
