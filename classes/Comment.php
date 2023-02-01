<?php

/**
 * Copyright 2014-2017 Christoph M. Becker
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
     * @param string $name
     * @param bool $visibleOnly
     * @param bool $ascending
     * @return Comment[]
     */
    public static function findByTopicname($name, $visibleOnly = false, $ascending = true)
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
     * @param string $id
     * @param string $topicname
     * @return ?Comment
     */
    public static function find($id, $topicname)
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
     * @param string $topicname
     * @return Comment
     */
    protected static function load($topicname, array $record)
    {
        $comment = new self($topicname, $record[1]);
        $comment->id = $record[0];
        $comment->user = $record[2];
        $comment->email = $record[3];
        $comment->message = $record[4];
        $comment->hidden = isset($record[5]) ? (bool) $record[5] : false;
        return $comment;
    }

    /**
     * @var string
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
     * @var string
     */
    protected $user;

    /**
     * @var string
     */
    protected $email;

    /**
     * @var string
     */
    protected $message;

    /**
     * @var bool
     */
    protected $hidden;

    /**
     * @param string $topicname
     * @param int $time
     * @return Comment
     */
    public static function make($topicname, $time)
    {
        return new self($topicname, $time);
    }

    /**
     * @param string $topicname
     * @param int $time
     */
    protected function __construct($topicname, $time)
    {
        $this->topicname = (string) $topicname;
        $this->time = (int) $time;
        $this->hidden = false;
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getTopicname()
    {
        return $this->topicname;
    }

    /**
     * @return int
     */
    public function getTime()
    {
        return $this->time;
    }

    /**
     * @return string
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * @return bool
     */
    public function isVisible()
    {
        return !$this->hidden;
    }

    /**
     * @param string $user
     */
    public function setUser($user)
    {
        $this->user = (string) $user;
    }

    /**
     * @param string $email
     */
    public function setEmail($email)
    {
        $this->email = (string) $email;
    }

    /**
     * @param string $message
     */
    public function setMessage($message)
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

    /** @param string $uniqid */
    public function insert($uniqid)
    {
        $this->id = $uniqid;
        Db::lock(LOCK_EX);
        $file = fopen(Db::getFoldername() . $this->topicname . '.' . self::EXT, 'a');
        fputcsv($file, $this->toRecord());
        fclose($file);
        Db::lock(LOCK_UN);
    }

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
     * @return array
     */
    protected function toRecord()
    {
        return array(
            $this->id, $this->time, $this->user, $this->email,
            $this->message, $this->hidden
        );
    }
}
