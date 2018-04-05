<?php

namespace Adept\Application;

use Adept\Application\Facades\Facade;
use Adept\Event\EventManagerInterface;
use Adept\Console\ConsoleApplication;
use Adept\Session\SessionInterface;
use Adept\Config\ConfigInterface;
use Adept\Route\RouterInterface;
use Adept\Http\RequestInterface;
use Adept\Log\LoggerInterface;
use Adept\Event\EventManager;
use Adept\Session\Session;
use Adept\Console\Command;
use Adept\Config\Config;
use Adept\Route\Router;
use Adept\Http\Request;
use Adept\Log\Log;
use Jshannon63\Cobalt\Container;
use DirectoryIterator;
use Dotenv\Dotenv;
use Exception;

/**
 * Class Application
 * @package Adept\Application
 */
class Application extends Container implements ApplicationInterface
{
    const VERSION = '0.1.0';

    protected $applicationPath;
    protected $configPath;
    protected $config;
    protected $providers = [];
    protected $controllers = [];
    protected $request;
    protected $router;
    protected $events;
    public $console;


    public function __construct($applicationPath, $configPath = null)
    {
        parent::__construct();
        putenv('APP_VERSION='.self::VERSION);
        $this->applicationPath = $applicationPath;
        $this->configPath = $configPath?$configPath:$applicationPath.'/config';
        $this->config = $this->loadConfiguration();
        $this->registerErrorHandler();
        $this->registerLogger();
        $this->initializeEventManager();
        $this['eventmanager']->trigger('config.loaded');
        if($this->consoleApp()) {
            $this->console = new ConsoleApplication(env('APP_NAME'), $this->version());
        }
    }

    /**
     * Run the application.
     *
     * @throws \Jshannon63\Cobalt\ContainerException
     */
    public function run()
    {
        $this->startSession();
        $this->getRequest();
        $this->intializeRouter();

        $this->registerClasses(config('app.providers') ,'registerProviders');

        $this->registerClasses([__DIR__.'/Facades'],'registerFacades');

        $this->registerClasses(config('app.events'),'registerEvents');
        $this->registerClasses(config('app.listeners'),'registerListeners');

        $this->bootProviders();

        $this->registerClasses(config('app.routes'),'registerRoutes');
        $this['router']->compile();
        $this['router']->process();

        $this->registerClasses(config('app.controllers'),'registerControllers');

        if($this->request()->getMethod()){
            echo $this['router']->dispatch();
        }

       if($this->consoleApp()) {
            $this->registerClasses(config('app.commands'),'registerCommands');
            $this->console->run();
        }

    }

    /**
     * @param $directories
     * @param $action
     */
    private function registerClasses($directories, $action){
//        dd($directories);
        foreach ($directories as $directory){
            $this->$action($directory);
        }
    }

    /**
     * Create and start the PHP session.
     *
     * @throws \Jshannon63\Cobalt\ContainerException
     * @throws \Jshannon63\Cobalt\NotFoundException
     */
    private function startSession(){
        $this->bind(SessionInterface::class, Session::class, true);
        $this->alias('session', SessionInterface::class);
    }

    /**
     * Process the incomming request.
     *
     * @throws \Jshannon63\Cobalt\ContainerException
     * @throws \Jshannon63\Cobalt\NotFoundException
     */
    private function getRequest(){
        $this->request = $this->make(RequestInterface::class, Request::class, true);
        $this->alias('request', RequestInterface::class);
    }

    /**
     * Instantiate Dotenv and Config and load settings.
     *
     * @throws \Jshannon63\Cobalt\ContainerException
     * @return array
     */
    private function loadConfiguration()
    {
        if (file_exists($this->applicationPath.'/'.'.env')) {
            $this->make('dotenv', function () {
                return new Dotenv($this->applicationPath);
            }, true)->load();
        } else {
            throw new exception('Missing environment file (.env) in application default applicationPath.');
        }
        if (is_dir($this->configPath)) {
            $this->make(ConfigInterface::class, function () {
                return new Config($this->configPath);
            }, true);
            $this->alias('config', ConfigInterface::class);
        } else {
            throw new exception('Missing configuration settings (/config directory) in application default applicationPath.');
        }
        return $this[ConfigInterface::class]->get();
    }

    /**
     * Load any externally created aliases from the application config file.
     * Config file is ($applicationPath/config/app.php)
     *
     */
    private function loadAliases()
    {
        foreach (config('app.aliases') as $key => $alias) {
            $this->alias($key, $alias);
        }
    }

    /**
     * Instantiate Whoops and setup for either web or console operation.
     *
     */
    private function registerErrorHandler()
    {
        if (config('app.debug')) {
            $whoops = new \Whoops\Run;
            if (!$this->consoleApp()) {
                $whoops->pushHandler(new \Whoops\Handler\PrettyPageHandler);
            } else {
                $whoops->pushHandler(new \Whoops\Handler\PlainTextHandler);
            }
            $whoops->register();
        }
    }

    /**
     * Instantiate the framework Logger.
     *
     * @throws \Jshannon63\Cobalt\ContainerException
     */
    private function registerLogger()
    {
        $this->bind(LoggerInterface::class, function () {
            return new Log($this->applicationPath.'/var/logs/app.log');
        }, true);
        $this->alias('logger', LoggerInterface::class);
    }

    /**
     * @throws \Jshannon63\Cobalt\ContainerException
     * @throws \Jshannon63\Cobalt\NotFoundException
     */
    private function initializeEventManager(){
        $this->make(EventManagerInterface::class, function () {
            return new EventManager($this->getContainer());
        }, true);
        $this->alias('eventmanager',EventManagerInterface::class);
    }

    /**
     * @param $directory
     * @throws \Jshannon63\Cobalt\ContainerException
     * @throws \Jshannon63\Cobalt\NotFoundException
     */
    private function registerEvents($directory){

        $events = getClassFiles($directory);

        foreach ($events as $count => $event){
            $this->bind(strtolower('App.Events.'.$event['basename']), $event['classname'], true);
            $this[strtolower('App.Events.'.$event['basename'])]->setName(strtolower('App.Events.'.$event['basename']));
        }
    }

    /**
     * @param $directory
     * @throws \Jshannon63\Cobalt\ContainerException
     * @throws \Jshannon63\Cobalt\NotFoundException
     */
    private function registerListeners($directory)
    {
        $listeners = getClassFiles($directory);

        foreach ($listeners as $count => $listeners) {
            $this->bind(strtolower('App.Listeners.'.$listeners['basename']), $listeners['classname'], true);
        }
    }

    /**
     * Register all externally declared service providers.
     *
     * @throws \Jshannon63\Cobalt\ContainerException
     */
    private function registerProviders($directory)
    {
        $providers = getClassFiles($directory);

        foreach ($providers as $provider){
            if(!class_exists($provider['classname'])){
                require_once $provider['pathname'];
            }
            if(get_parent_class($provider['classname']) != ServiceProvider::class){
                throw new Exception('Attempt to register '.$provider['classname'].
                    ' as a ServiceProvider failed. A Service Provider must extend ',ServiceProvider::class);
            }
            $this->bind($provider['classname'], $provider['classname'], true);
            $this->alias($provider['basename'], $provider['classname']);
            $this[$provider['basename']]->register($this);
            $this->providers[] = $provider['basename'];
        }
    }

    /**
     * Boot all externally declared service providers.
     *
     */
    private function bootProviders()
    {
        foreach ($this->providers as $provider) {
            $this->$provider()->boot($provider);
        }
    }

    /**
     * Initialize the Router.
     *
     * @throws \Jshannon63\Cobalt\ContainerException
     * @throws \Jshannon63\Cobalt\NotFoundException
     */
    private function intializeRouter(){
        $this->make(RouterInterface::class, function () {
            return new Router(app());
        }, true);
        $this->alias('router', RouterInterface::class);
        $this->router = $this['router'];

        if (config('app.router.cached')) {
            $this->router()->cache($this->path().config('app.router.cachefile'));
        }
    }

    /**
     * Register Route Files
     * @param $directory
     * @throws \Jshannon63\Cobalt\ContainerException
     * @throws \Jshannon63\Cobalt\NotFoundException
     */
    private function registerRoutes($directory){

        $routefiles = getClassFiles($directory);

        foreach($routefiles as $count => $routefile){
            if (config('app.router.cached') && file_exists($this->path().config('app.router.cachefile'))) {
                if (filemtime($routefile['pathname']) > filemtime($this->path().config('app.router.cachefile'))) {
                    $this->router()->bustCache();
                }
            }
            require_once $routefile['pathname'];
        }
    }


    /**
     * Register externally declared controllers
     *
     * @param $directory
     * @throws \Jshannon63\Cobalt\ContainerException
     * @throws \Jshannon63\Cobalt\NotFoundException
     */
    private function registerControllers($directory)
    {
        $controllers = getClassFiles($directory);

        foreach ($controllers as $controller){
            if(!class_exists($controller['classname'])){
                require_once $controller['pathname'];
            }
            if(get_parent_class($controller['classname']) != Controller::class){
                throw new Exception('Attempt to register '.$controller['classname'].
                    ' as a Controller failed. A Controller must extend ',Controller::class);
            }
            $this->bind($controller['classname'], $controller['classname'], true);
            $this->alias($controller['basename'], $controller['classname']);
            $this->controllers[] = $controller['basename'];
        }
    }

    /**
     * Register all externally declared commands.
     */
    private function registerCommands($directory)
    {
        $commands = getClassFiles($directory);

        foreach ($commands as $command){
            if(!class_exists($command['classname'])){
                require_once $command['pathname'];
            }
            if(get_parent_class($command['classname']) != Command::class){
                throw new Exception('Attempt to register '.$command['classname'].
                    ' as a Command failed. A Command must extend ',Command::class);
            }
            $this->bind($command['classname'], $command['classname'], true);
            $this->console->add($this[$command['classname']]);
        }
    }

    /**
     * Register all externally declared facades.
     */
    private function registerFacades($directory)
    {
        $facades = getClassFiles($directory);

        foreach ($facades as $facade){
            if($facade['classname'] == Facade::class){
                continue;
            }
            if(!class_exists($facade['classname'])){
                require_once $facade['pathname'];
            }
            if(get_parent_class($facade['classname']) != Facade::class){
                throw new Exception('Attempt to register '.$facade['classname'].
                    ' as a Facade failed. A Facade must extend '.Facade::class);
            }
        }
    }

    /**
     * Return the default application applicationPath.
     *
     * @return string
     */
    public function path()
    {
        return $this->applicationPath;
    }

    /**
     * Check if we are in console mode.
     *
     * @return bool
     */
    public function consoleApp()
    {
        return ('cli' === PHP_SAPI);
    }

    /**
     * Return the application version.
     *
     * @return string
     */
    public function version()
    {
        return self::VERSION;
    }

    /**
     * For any unrecognized method we will check the container for a binding
     * which matches the requested method.
     *
     * @param $method
     * @param $args
     * @return object
     */
    public function __call($method, $args)
    {
        if ($this->has($method)) {
            return $this[$method];
        }
        throw new \BadMethodCallException("Method $method is not a valid application method or container binding.");
    }

}
