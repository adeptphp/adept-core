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

    protected $action;

    protected $type;

    protected $regex;

    /** @var array $routeInfo */
    protected $routeInfo;

    protected $middleware;

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
        $this->redirect = null;
    }

    public function name(string $name)
    {
        $this->name = $name;
        return $this;
    }

    public function uri(string $uri = null){
        if(is_null($uri)){
            return $this->uri;
        }
        $this->uri = $uri;
    }

    public function regex(string $regex = null){
        if(is_null($regex)){
            return $this->regex;
        }
        $this->regex = $regex;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setRouteInfo(array $routeInfo)
    {
        $this->routeInfo = $routeInfo;
        return $this;
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

    public function middleware($middleware)
    {
        if(is_string($middleware)){
            $this->middleware = [$middleware];
        } elseif(is_array($middleware)) {
            $this->middleware = $middleware;
        } else{
            throw new \RuntimeException('Middleware must be a string or array of middleware names.');
        }
        return $this;
    }

    public function getMiddleware()
    {
        return $this->middleware;
    }

    public function setRedirect($redirect, $code){
        $this->redirect = [$redirect, $code];
        return $this;
    }

    public function type(string $type = null){
        if(is_null($type)){
            return $this->type;
        }
        $this->type = $type;
    }

    public function action($action = null){
        if(is_null($action)){
            return $this->action;
        }
        $this->action = $action;
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
