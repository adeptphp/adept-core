<?php

namespace Adept\Route;


class Group
{
    protected $prefix;
    protected $callable;

    public function __construct($prefix, callable $callback)
    {
        $this->prefix = $prefix;
        $this->callback = $callback;
    }

    public function getPrefix(){
        return $this->prefix;
    }

    public function getCallback(){
        return $this->callback;
    }

}