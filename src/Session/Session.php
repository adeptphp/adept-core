<?php

namespace Adept\Session;

class Session
{
    public function __construct()
    {
        session_start();
    }
}
