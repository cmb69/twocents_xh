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

namespace Twocents\Value;

class Comment
{
    /** @var string|null */
    private $id;

    /** @var string */
    private $topicname;

    /** @var int */
    private $time;

    /** @var string */
    private $user;

    /** @var string */
    private $email;

    /** @var string */
    private $message;

    /** @var bool */
    private $hidden;

    public function __construct(
        ?string $id,
        string $topicname,
        int $time,
        string $user,
        string $email,
        string $message,
        bool $hidden
    ) {
        $this->id = $id;
        $this->topicname = $topicname;
        $this->time = $time;
        $this->user = $user;
        $this->email = $email;
        $this->message = $message;
        $this->hidden = $hidden;
    }

    public function id(): ?string
    {
        return $this->id;
    }

    public function topicname(): string
    {
        return $this->topicname;
    }

    public function time(): int
    {
        return $this->time;
    }

    public function user(): string
    {
        return $this->user;
    }

    public function email(): string
    {
        return $this->email;
    }

    public function message(): string
    {
        return $this->message;
    }

    public function hidden(): bool
    {
        return $this->hidden;
    }

    public function withId(string $id): self
    {
        assert($this->id === null);
        $that = clone $this;
        $that->id = $id;
        return $that;
    }

    public function with(string $user, string $email, string $message): self
    {
        $that = clone $this;
        $that->user = $user;
        $that->email = $email;
        $that->message = $message;
        return $that;
    }

    public function withMessage(string $message): self
    {
        $that = clone $this;
        $that->message = $message;
        return $that;
    }

    public function withToggledVisibility(): self
    {
        $that = clone $this;
        $that->hidden = !$that->hidden;
        return $that;
    }

    /** @return array{string,int,string,string,string,int} */
    public function toRecord()
    {
        assert($this->id !== null);
        return array(
            $this->id, $this->time, $this->user, $this->email,
            $this->message, (int) $this->hidden
        );
    }
}
