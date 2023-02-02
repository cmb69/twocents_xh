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

class Comment
{
    const EXT = 'csv';

    /**
     * @return array<self>
     */
    public static function findByTopicname(string $name, bool $visibleOnly = false, bool $ascending = true): array
    {
        $comments = array();
        Db::lock(LOCK_SH);
        $filename = Db::getFoldername() . $name . '.' . self::EXT;
        if (is_readable($filename) && ($file = fopen($filename, 'r'))) {
            while (($record = fgetcsv($file)) !== false) {
                $comment = self::load($name, $record);
                if (!$visibleOnly || ($comment->isVisible() || (defined('XH_ADM') && XH_ADM))) {
                    $comments[] = $comment;
                }
            }
            fclose($file);
        }
        Db::lock(LOCK_UN);
        $order = $ascending ? 1 : -1;
        usort($comments, function ($a, $b) use ($order) {
            return ($a->time - $b->time) * $order;
        });
        return $comments;
    }

    /**
     * @return ?self
     */
    public static function find(string $id, string $topicname)
    {
        $comments = self::findByTopicname($topicname);
        foreach ($comments as $comment) {
            if ($comment->getId() == $id) {
                return $comment;
            }
        }
        return null;
    }

    /**
     * @param array<string> $record
     */
    protected static function load(string $topicname, array $record): Comment
    {
        $comment = new self($topicname, (int) $record[1]);
        $comment->id = $record[0];
        $comment->user = $record[2];
        $comment->email = $record[3];
        $comment->message = $record[4];
        $comment->hidden = isset($record[5]) ? (bool) $record[5] : false;
        return $comment;
    }

    /**
     * @var string|null
     */
    protected $id;

    /**
     * @var string
     */
    protected $topicname;

    /**
     * @var int
     */
    protected $time;

    /**
     * @var string|null
     */
    protected $user;

    /**
     * @var string|null
     */
    protected $email;

    /**
     * @var string|null
     */
    protected $message;

    /**
     * @var bool
     */
    protected $hidden;

    public static function make(string $topicname, int $time): self
    {
        return new self($topicname, $time);
    }

    protected function __construct(string $topicname, int $time)
    {
        $this->topicname = (string) $topicname;
        $this->time = (int) $time;
        $this->hidden = false;
    }

    /** @return string|null */
    public function getId()
    {
        return $this->id;
    }

    public function getTopicname(): string
    {
        return $this->topicname;
    }

    public function getTime(): int
    {
        return $this->time;
    }

    /** @return string|null */
    public function getUser()
    {
        return $this->user;
    }

    /** @return string|null */
    public function getEmail()
    {
        return $this->email;
    }

    /** @return string|null */
    public function getMessage()
    {
        return $this->message;
    }

    public function isVisible(): bool
    {
        return !$this->hidden;
    }

    /**
     * @return void
     */
    public function setUser(string $user)
    {
        $this->user = (string) $user;
    }

    /**
     * @return void
     */
    public function setEmail(string $email)
    {
        $this->email = (string) $email;
    }

    /**
     * @return void
     */
    public function setMessage(string $message)
    {
        $this->message = (string) $message;
    }

    /**
     * @return void
     */
    public function hide()
    {
        $this->hidden = true;
    }

    /**
     * @return void
     */
    public function show()
    {
        $this->hidden = false;
    }

    /**
     * @return void
     */
    public function insert(string $uniqid)
    {
        $this->id = $uniqid;
        Db::lock(LOCK_EX);
        $file = fopen(Db::getFoldername() . $this->topicname . '.' . self::EXT, 'a');
        fputcsv($file, $this->toRecord());
        fclose($file);
        Db::lock(LOCK_UN);
    }

    /** @return void */
    public function update()
    {
        Db::lock(LOCK_EX);
        $file = fopen(Db::getFoldername() . $this->topicname . '.' . self::EXT, 'r+');
        $temp = fopen('php://temp', 'w+');
        while (($record = fgetcsv($file)) !== false) {
            if ($record[0] != $this->id) {
                fputcsv($temp, $record);
            } else {
                fputcsv($temp, $this->toRecord());
            }
        }
        ftruncate($file, 0);
        rewind($file);
        rewind($temp);
        stream_copy_to_stream($temp, $file);
        fclose($file);
        fclose($temp);
        Db::lock(LOCK_UN);
    }

    /** @return void */
    public function delete()
    {
        Db::lock(LOCK_EX);
        $file = fopen(Db::getFoldername() . $this->topicname . '.' . self::EXT, 'r+');
        $temp = fopen('php://temp', 'w+');
        while (($record = fgetcsv($file)) !== false) {
            if ($record[0] != $this->id) {
                fputcsv($temp, $record);
            }
        }
        ftruncate($file, 0);
        rewind($file);
        rewind($temp);
        stream_copy_to_stream($temp, $file);
        fclose($file);
        fclose($temp);
        Db::lock(LOCK_UN);
    }

    /**
     * @return array{string,int,string,string,string,bool}
     */
    protected function toRecord()
    {
        return array(
            $this->id, $this->time, $this->user, $this->email,
            $this->message, $this->hidden
        );
    }
}
