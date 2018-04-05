<?php

namespace Adept\Event;

interface ListenerInterface
{
    public function setName($name);

    public function handle(Event $event);
}
