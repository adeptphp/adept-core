<?php

namespace Adept\Route;

class Route implements RouteInterface
{
    /** @var string */
    protected $method;

    /** @var string */
    protected $uri;

    /** @var string */
    protected $name;

    /** @var array $routeInfo */
    protected $routeInfo;

    /**
     * Constructs a route (value object).
     *
     * @param string $method
     * @param string  $uri
     * @param mixed $action
     */
    public function __construct($method, $uri)
    {
        $this->method = $method;
        $this->uri = $uri;
        $this->name = '';
    }

    public function setName(string $name)
    {
        $this->name = $name;
        return $this;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setRouteInfo(array $routeInfo)
    {
        $this->routeInfo = $routeInfo;
    }

    public function getRouteInfo(){
        return $this->routeInfo;
    }

    public function getUri(){
        return $this->uri;
    }

    public function getMethod(){
        return $this->method;
    }


    /**
     * Tests whether this route matches the given string.
     *
     * @param string $str
     *
     * @return bool
     */
    public function matches($str)
    {
        $uri = '~^'.$this->uri.'$~';
        return (bool) preg_match($uri, $str);
    }
}
