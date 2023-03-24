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

use Twocents\Value\Url;

class Request
{
    /** @codeCoverageIgnore */
    public static function current(): self
    {
        return new self;
    }

    public function url(): Url
    {
        $rest = $this->query();
        if ($rest !== "") {
            $rest = "?" . $rest;
        }
        return Url::from(CMSIMPLE_URL . $rest);
    }

    /** @codeCoverageIgnore */
    public function admin(): bool
    {
        return defined('XH_ADM') && XH_ADM;
    }

    /** @codeCoverageIgnore */
    protected function query(): string
    {
        return $_SERVER["QUERY_STRING"];
    }

    /** @codeCoverageIgnore */
    public function time(): int
    {
        return (int) $_SERVER["REQUEST_TIME"];
    }

    public function action(): string
    {
        $action = $this->url()->param("twocents_action");
        if (!is_string($action)) {
            return "";
        }
        if (!strncmp($action, "do_", strlen("do_"))) {
            return "";
        }
        if (array_key_exists("twocents_do", $this->post())) {
            return "do_$action";
        }
        return $action;
    }

    /** @return array{user:string,email:string,message:string} */
    public function commentPost(): array
    {
        return [
            "user" => $this->trimmedPostString("twocents_user"),
            "email" => $this->trimmedPostString("twocents_email"),
            "message" => $this->trimmedPostString("twocents_message"),
        ];
    }

    private function trimmedPostString(string $name): string
    {
        $post = $this->post();
        return isset($post[$name]) && is_string($post[$name]) ? trim($post[$name]) : "";
    }

    /**
     * @return array<string|array<string>>
     * @codeCoverageIgnore
     */
    protected function post(): array
    {
        return $_POST;
    }
}
