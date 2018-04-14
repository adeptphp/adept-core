<?php

namespace Adept\Route;

use Psr\Container\ContainerInterface;

interface RouterInterface
{
    public function __construct(ContainerInterface $container, Array $config);

    public function dispatch($method, $uri);

    public function add($method, $uri, $action);

    public function group($prefix, callable $callback);

    public function bustCache();
}
