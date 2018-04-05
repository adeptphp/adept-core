<?php

namespace Adept\Event;

class Listener implements ListenerInterface
{
    protected $name;

    public function setName($name)
    {
        $this->name = $name;
    }

    public function handle(Event $event)
    {
        //
    }
}
