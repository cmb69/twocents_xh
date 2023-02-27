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

use Error;
use Twocents\Value\Comment;

class FakeDb extends Db
{
    /** @var string */
    private $foldername = "db_folder";

    /** @var array<string,array<Comment> */
    private $data = [];

    /** @var string|null */
    public $lastTopicStored = null;

    public function __construct() {}

    public function getFoldername(): string
    {
        return $this->foldername;
    }

    public function lock(bool $exclusive) {}

    public function unlock($lock) {}

    public function findTopics(string $extension = "csv"): array
    {
        return array_keys($this->data);
    }

    public function findCommentsOfTopic(string $topic, bool $visibleOnly = false): array
    {
        $comments = $this->data[$topic];
        if (!$visibleOnly) {
            return $comments;
        }
        return array_filter($comments, function (Comment $comment) {
            return !$comment->hidden();
        });
    }

    public function findCommentsOfGbookTopic(string $topic): array
    {
        return $this->data[$topic];
    }

    public function findCommentsOfCommentsTopic(string $topic): array
    {
        return $this->data[$topic];
    }

    public function storeTopic(string $topic, array $comments)
    {
        foreach ($comments as $comment) {
            if ($comment->topicname() !== $topic) {
                throw new Error("topic mismatch");
            }
        }
        $this->data[$topic] = $comments;
        $this->lastTopicStored = $topic;
    }

    public function findComment(string $topic, string $id): ?Comment
    {
        foreach ($this->data[$topic] as $comment) {
            if ($comment->id() === $id) {
                return $comment;
            }
        }
        return null;
    }

    public function insertComment(Comment $comment)
    {
        $this->data[$comment->topicname()][] = $comment;
    }

    public function updateComment(Comment $comment)
    {
        foreach ($this->data[$comment->topicname()] as &$aComment) {
            if ($aComment->id() === $comment->id()) {
                $aComment = $comment;
            }
        }
    }

    public function deleteComment(Comment $comment)
    {
        foreach ($this->data[$comment->topicname()] as $i => $aComment) {
            if ($aComment->id() === $comment->id()) {
                unset($this->data[$comment->topicname()][$i]);
            }
        }
    }
}