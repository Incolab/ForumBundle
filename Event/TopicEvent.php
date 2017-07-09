<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Incolab\ForumBundle\Event;

use Symfony\Component\EventDispatcher\Event;
use Incolab\ForumBundle\Entity\Topic;

/**
 * Description of MessageEvent
 *
 * @author david
 */
class TopicEvent extends Event {

    const VALID = 0;
    const NOT_VALID = 1;

    protected $topic;
    protected $status;
    protected $messageStatus;

    public function __construct(Topic $topic) {
        $this->topic = $topic;
        $this->status = self::VALID;
        $this->messageStatus = "";
    }

    // Le listener doit avoir accès au topic
    public function getTopic(): Topic {
        return $this->topic;
    }

    // Le listener doit pouvoir modifier le message
    public function setTopic(Topic $topic): TopicEvent {
        $this->topic = $topic;
        return $this;
    }

    public function setStatus(int $status): TopicEvent {
        $this->status = $status;
        return $this;
    }

    public function isValid(): bool {
        if ($this->status === self::VALID) {
            return true;
        }

        return false;
    }
    
    public function setMessageStatus(string $msg): TopicEvent {
        $this->messageStatus = $msg;
        return $this;
    }
    
    public function getMessageStatus(): string {
        return $this->messageStatus;
    }

}
