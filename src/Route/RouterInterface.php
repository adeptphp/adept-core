<?php

namespace Adept\Route;

use Jshannon63\Cobalt\ContainerInterface;

interface RouterInterface
{
    public function __construct(ContainerInterface $container);

    public function compile();

    public function process($method = null, $uri = null);

    public function dispatch();

    public function add($method, $uri, $action);

    public function group($prefix, callable $callback);

    public function list();

    public function cache($cachefile = null);

    public function bustCache();
}
