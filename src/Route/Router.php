<?php

namespace Adept\Route;

use FastRoute\DataGenerator\GroupCountBased as DataGenerator;
use FastRoute\Dispatcher\GroupCountBased as Dispatcher;
use Adept\Http\RequestInterface;
use FastRoute\RouteCollector as Collector;
use Jshannon63\Cobalt\ContainerInterface;
use FastRoute\RouteParser\Std as Parser;
use Jshannon63\Cobalt\Container;

/**
 * Class Router
 * @package Adept\Route
 */
class Router extends Collector implements RouterInterface
{
    protected $container;
    protected $dispatcher;
    protected $request;
    protected $cache;
    protected $config;
    protected $routes;
    protected $currentRoute;
    protected $routeInfo;

    public function __construct(ContainerInterface $container = null)
    {
        parent::__construct(new Parser(), new DataGenerator());
        $this->container = $container ? $container : new Container;
        $this->request = $this->container->get(RequestInterface::class);
        $this->cache = null;
        $this->routes = [];
        return $this;
    }

    public function compile()
    {
        if ($this->cache) {
            if (file_exists($this->cache)) {
                $dispatchData = require $this->cache;
                if (!is_array($dispatchData)) {
                    throw new \RuntimeException('Invalid router cache file "'.$this->cache.'"');
                }
                $this->dispatcher = new Dispatcher($dispatchData);
            } else {
                $dispatchData = $this->getData();
                $this->dispatcher = new Dispatcher($dispatchData);
                file_put_contents($this->cache, '<?php return '.var_export($dispatchData, true).';');
            }
        } else {
            $dispatchData = $this->getData();
            $this->dispatcher = new Dispatcher($dispatchData);
        }
    }

    public function process($method = null, $uri = null){
        if(is_null($method)){
            $method = $this->request->getMethod();
        }

        if(is_null($uri)){
            $uri = $this->request->getUri();
        }

        $routeInfo = $this->dispatcher->dispatch($method, $uri);

        if($routeInfo[0] != Dispatcher::FOUND){
            $this->currentRoute == null;
            $this->routeInfo = $routeInfo;
            return;
        }

        $this->routes['route.'.$method.'.'.str_slug($uri)]->setRouteInfo($routeInfo);
        $this->currentRoute = $this->routes['route.'.$method.'.'.str_slug($uri)];
        $this->request->setRoute($this->currentRoute);
    }

    public function dispatch()
    {
        $routeInfo = $this->currentRoute?$this->currentRoute->getRouteInfo():$this->routeInfo;
        switch ($routeInfo[0]) {
            case Dispatcher::NOT_FOUND:
                header($_SERVER['SERVER_PROTOCOL'].' 404 Not Found', true, 404);
                return;
            case Dispatcher::METHOD_NOT_ALLOWED:
                $allowedMethods = $routeInfo[1];
                header($_SERVER['SERVER_PROTOCOL'].' 405 Method Not Allowed', true, 405);
                return;
            case Dispatcher::FOUND:
                $handler = $routeInfo[1];
                $params = $routeInfo[2];
                foreach ($params as $key => $param) {
                    if (is_string($param)) {
                        $params[$key] = urldecode($param);
                    }
                }
                if (is_callable($handler)) {
                    return $handler();
                } elseif (false !== $pos = strpos($handler, '@')) {
                    list($class, $method) = explode('@', $handler);
                    if (!$this->container->has($class)) {
                        throw new \Exception('Class '.$class.' referenced in a route binding was not found in the application container.');
                    }
                    return call_user_func_array([$this->container[$class], $method], $params);
                } elseif ($this->container->has($handler)) {
                    return call_user_func_array($this->container->getBinding($handler)['concrete'], $params);
                }
                header($_SERVER['SERVER_PROTOCOL'].' 204 No Content', true, 204);
                return;
            default:
                header($_SERVER['SERVER_PROTOCOL'].' 500 Internal Server Error', true, 500);
                return;
        }
    }

    /**
     * @param $method
     * @param $uri
     * @param $action
     * @return object|void
     */
    public function add($method, $uri, $action)
    {
        if (is_callable($action)) {
            $name = 'route.action.'.str_slug($method.$uri);
            $this->container->bind($name, $action, true);
            $this->addRoute($method, $uri, $name);
            return $this->routes['route.'.$method.'.'.str_slug($uri)] = new Route($method, $uri, $name);
        }
        $this->addRoute($method, $uri, $action);
        return $this->routes['route.'.$method.'.'.str_slug($this->currentGroupPrefix.$uri)] = new Route($method, $this->currentGroupPrefix.$uri);
    }

    public function group($prefix, callable $callback)
    {
        $this->addGroup($prefix, $callback);
    }

    public function routes(){
        return $this->routes;
    }

    public function groups(){
        return $this->groups;
    }

    public function list()
    {
        return $this->getData();
    }

    public function cache($cachefile = null)
    {
        $this->cache = $cachefile;
    }

    public function bustCache()
    {
        if (file_exists($this->cache)) {
            unlink($this->cache);
        }
    }
}
