<?php

namespace Adept\Application;

abstract class Controller
{
    protected $app;

    public function __construct()
    {
        $this->app = Application::getContainer();
    }
}
