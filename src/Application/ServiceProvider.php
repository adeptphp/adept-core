<?php

namespace Adept\Application;

abstract class ServiceProvider
{
    protected $app;

    public function __construct()
    {
        $this->app = Application::getContainer();
    }

    /**
     *
     * @return void
     */
    public function boot()
    {
        //
    }

    /**
     *
     * @return void
     */
    public function register()
    {
        //
    }
}
