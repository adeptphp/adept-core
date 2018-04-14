<?php

namespace Adept\Route;

use FastRoute\DataGenerator\GroupCountBased as DataGenerator;
use FastRoute\Dispatcher\GroupCountBased as Dispatcher;
use FastRoute\RouteCollector as Collector;
use FastRoute\RouteParser\Std as Parser;
use Psr\Container\ContainerInterface;

/**
 * Class Router
 * @package Adept\Route
 */
class Router extends Collector implements RouterInterface
{
    protected $container;
    protected $dispatcher;
    protected $cache;
    protected $cachePath;
    protected $staticRoot;
    protected $routes;
    protected $currentRoute;
    protected $routeInfo;
    protected $middleware;
    protected $processingGroup;

    public function __construct(ContainerInterface $container, Array $config = null)
    {
        parent::__construct(new Parser(), new DataGenerator());
        $this->container = $container;
        $this->cached = isset($config['cached'])?true:false;
        $this->cachePath = isset($config['cachePath'])?$config['cachePath']:sys_get_temp_dir().'routescache.php';
        $this->staticRoot = isset($config['staticRoot'])?$config['staticRoot']:$_SERVER['DOCUMENT_ROOT'];
        $this->routes = [];
        $this->currentRoute = null;
        $this->processingGroup = false;
        return $this;
    }

    private function compileRoutes()
    {
        if ($this->cached) {
            if (file_exists($this->cache)) {
                $dispatchData = require $this->cachePath;
                if (!is_array($dispatchData)) {
                    throw new \RuntimeException('Invalid router cache file "'.$this->cachePath.'"');
                }
                $this->dispatcher = new Dispatcher($dispatchData);
            } else {
                $this->dispatcher = new Dispatcher($this->getData());
                file_put_contents($this->cachePath, '<?php return '.var_export($this->getData(), true).';');
            }
        } else {
            $this->dispatcher = new Dispatcher($this->getData());
        }
    }

    private function evaluateUri($method, $uri){
        $routeInfo = $this->dispatcher->dispatch($method, $uri);

        if($routeInfo[0] != Dispatcher::FOUND){
            $this->currentRoute == null;
            $this->routeInfo = $routeInfo;
        } else{
            $this->currentRoute = $this->routes[$routeInfo[1]]->setRouteInfo($routeInfo);
            if($this->currentRoute->type() === 'redirect'){
                list($redirectUrl, $responseCode) = $this->currentRoute->action();
                header('Location: '.$redirectUrl, true, $responseCode);
                exit;
            }
            $this->currentRoute->uri($uri);
        }
    }

    public function dispatch($method, $uri)
    {
        $this->compileRoutes();
        $this->evaluateUri($method, $uri);
        $routeInfo = $this->currentRoute?$this->currentRoute->getRouteInfo():$this->routeInfo;
        switch ($routeInfo[0]) {
            case Dispatcher::NOT_FOUND:
                return [404, $_SERVER['SERVER_PROTOCOL'].' 404 Not Found'];
            case Dispatcher::METHOD_NOT_ALLOWED:
                return [405, $_SERVER['SERVER_PROTOCOL'].' 405 Method Not Allowed. Allowed methods: '.implode(", ",$routeInfo[1])];
            case Dispatcher::FOUND:
                $params = $routeInfo[2];
                foreach ($params as $key => $param) {
                    if (is_string($param)) {
                        $params[$key] = urldecode($param);
                    }
                }
                if ($this->currentRoute->type() === 'controller') {
                    list($class, $method) = explode('@', $this->currentRoute->action());
                    if (!$this->container->has($class)) {
                        throw new \Exception('Object '.$class.' referenced in a route binding was not found in the container.');
                    }
                    if(!method_exists($this->container->get($class) , $method)){
                        throw new \Exception('Method: '.$method. ' does not exist for Object: '.$class.' referenced in a route binding.');
                    }
                    return [200, call_user_func_array([$this->container->get($class), $method], $params)];
                } elseif ($this->currentRoute->type() === 'callable') {
                    return [200, call_user_func_array($this->currentRoute->action(), $params)];
                } elseif ($this->currentRoute->type() === 'static'){
                    return [200, file_get_contents($this->currentRoute->action())];
                }
                return [204, $_SERVER['SERVER_PROTOCOL'].' 204 No Content'];
            default:
                return [500, $_SERVER['SERVER_PROTOCOL'].' 500 Internal Server Error'];
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
        $this->routes[] = new Route($method, $uri);
        end($this->routes);
        $key = key($this->routes);
        if (is_callable($action)) {
            $this->routes[$key]->type('callable');
        } elseif(strpos($action,'@')){
            $this->routes[$key]->type('controller');
        } elseif($action === 'redirect'){
            $this->routes[$key]->type('redirect');
        } elseif(is_file($action)) {
            $this->routes[$key]->type('static');
        } else {
            throw new \RuntimeException('Unable to parse route action: '.$action);
        }
        $this->routes[$key]->regex($uri);
        $this->routes[$key]->action($action);
        $this->addRoute($method, $uri, $key);

        if($this->processingGroup && !is_null($this->middleware)){
            $this->routes[$key]->middleware($this->middleware);
        }

        return $this->routes[$key];
    }

    public function group($prefix, callable $callback)
    {
        $this->processingGroup = true;
        $previousGroupPrefix = $this->currentGroupPrefix;
        $this->currentGroupPrefix = $previousGroupPrefix.$prefix;
        $callback($this);
        $this->currentGroupPrefix = $previousGroupPrefix;
        $this->middleware = null;
        $this->processingGroup = false;
    }

    public function middleware($middleware){
        if(is_string($middleware)){
            $this->middleware = [$middleware];
        } elseif(is_array($middleware)) {
            $this->middleware = $middleware;
        } else{
            throw new \RuntimeException('Middleware names must be in a string or array format.');
        }
        return $this;
    }

    public function redirect($method, $original, $redirect, $code){
        return $this->add($method, $original,'redirect')
            ->action([$redirect, $code]);
    }

    public function get($uri, $action)
    {
        return $this->add('GET', $uri, $action);
    }

    public function post($uri, $action)
    {
        return $this->add('POST', $uri, $action);
    }

    public function put($uri, $action)
    {
        return $this->add('PUT', $uri, $action);
    }

    public function delete($uri, $action)
    {
        return $this->add('DELETE', $uri, $action);
    }
    public function patch($uri, $action)
    {;
        return $this->add('PATCH', $uri, $action);
    }

    public function head($uri, $action)
    {
        return $this->add('HEAD', $uri, $action);
    }

    public function routes(){
        return $this->routes;
    }

    public function bustCache()
    {
        if (file_exists($this->cachePath)) {
            unlink($this->cachePath);
        }
    }
    /**
     * Sets and/or gets the current route
     *
     * @param Route|null $route
     * @return Route
     */
    public function currentRoute(Route $route=null): Route
    {
        if(!is_null($route)){
            $this->currentRoute = $route;
        }
        return $this->currentRoute;
    }
}
