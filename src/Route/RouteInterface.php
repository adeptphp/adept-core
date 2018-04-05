<?php

namespace Adept\Route;

interface RouteInterface
{
    /**
     * Constructs a route (value object).
     *
     * @param string $method
     * @param string  $uri
     * @param mixed $action
     */
    public function __construct($method, $uri);

    public function setName(string $name);

    public function getName();

    public function setRouteInfo(array $routeInfo);

    public function getRouteInfo();

    public function getUri();

    public function getMethod();

    /**
     * Tests whether this route matches the given string.
     *
     * @param string $str
     *
     * @return bool
     */
    public function matches($str);
}
