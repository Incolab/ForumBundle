<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Incolab\ForumBundle\Event;

use Symfony\Component\EventDispatcher\Event;
use Incolab\ForumBundle\Entity\Post;

/**
 * Description of MessageEvent
 *
 * @author david
 */
class PostEvent extends Event {

    const VALID = 0;
    const NOT_VALID = 1;

    protected $post;
    protected $status;
    protected $messageStatus;

    public function __construct(Post $post) {
        $this->post = $post;
        $this->status = self::VALID;
        $this->messageStatus = "";
    }

    // Le listener doit avoir accès au message
    public function getPost(): Post {
        return $this->post;
    }

    // Le listener doit pouvoir modifier le message
    public function setPost(Post $post): PostEvent {
        $this->post = $post;
        return $this;
    }

    public function setStatus(int $status): PostEvent {
        $this->status = $status;
        return $this;
    }

    public function isValid(): bool {
        if ($this->status === self::VALID) {
            return true;
        }

        return false;
    }
    
    public function setMessageStatus(string $msg): PostEvent {
        $this->messageStatus = $msg;
        return $this;
    }
    
    public function getMessageStatus(): string {
        return $this->messageStatus;
    }

}
