<?php

namespace Adept\Application;

abstract class Model
{
    protected $app;

    public function __construct()
    {
        $this->app = Application::getContainer();
    }
}
