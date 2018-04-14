<?php

namespace Adept\Application;

use Adept\Event\EventManagerInterface;
use Adept\Application\Facades\Facade;
use Adept\Console\ConsoleApplication;
use Adept\Session\SessionInterface;
use Adept\Config\ConfigInterface;
use Jshannon63\Cobalt\Container;
use Adept\Route\RouterInterface;
use Adept\Http\RequestInterface;
use Adept\Log\LoggerInterface;
use Adept\Event\EventManager;
use Adept\Session\Session;
use Adept\Console\Command;
use Adept\Config\Config;
use Adept\Route\Router;
use Adept\Http\Request;
use Adept\Console\Cron;
use DirectoryIterator;
use Adept\Log\Log;
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
    protected $providers;
    protected $controllers;
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
        if(!config('app.key')){
            throw new \RuntimeException('Application key is required to be set in environment.');
        }
        $this->providers = [];
        $this->controllers = [];
        date_default_timezone_set(config('app.timezone'));
        $this->registerErrorHandler();
        $this->registerLogger();
        $this->initializeEventManager();
    }

    /**
     * Run the application.
     *
     * @param null $mode
     */
    public function run($mode = 'http')
    {
        if($mode === 'http'){
            $this->startSession();
            $this->intializeRouter();
        }

        $this->registerProviders();
        $this->registerFacades();
        $this->registerEvents();
        $this->registerListeners();
        $this->bootProviders();
        $this->registerControllers();


        if($mode == 'http'){
            $this->getRequest();
            $this->registerRoutes();
            list($responseCode, $responseBody) = $this['router']->dispatch($this->request()->getMethod(), $this->request()->getUri());
            http_response_code($responseCode);
            echo $responseBody;
        }

        if($mode == 'console' && $this->consoleApp()) {
            $this->console = new ConsoleApplication(env('APP_NAME'), $this->version());
            $this->registerCommands();
            $this->console->run();
        }

        if($mode == 'cron' && $this->consoleApp()) {
            $this->registerCrons();
        }
    }

    /**
     * Create and start the PHP session.
     *
     * @throws \Jshannon63\Cobalt\ContainerException
     * @throws \Jshannon63\Cobalt\NotFoundException
     */
    private function startSession()
    {
        $this->bind(SessionInterface::class, Session::class, true);
        $this->alias('session', SessionInterface::class);
    }

    /**
     * Process the incomming request.
     *
     * @throws \Jshannon63\Cobalt\ContainerException
     * @throws \Jshannon63\Cobalt\NotFoundException
     */
    private function getRequest()
    {
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
            throw new exception('Missing environment file (.env). Not found in application path '.$this->applicationPath.'.');
        }
        if (is_dir($this->configPath)) {
            $this->make(ConfigInterface::class, function () {
                return new Config($this->configPath);
            }, true);
            $this->alias('config', ConfigInterface::class);
        } else {
            throw new exception('Missing configuration settings (/config directory). Not found in configuration path '.$this->configPath.'.');
        }
        return $this[ConfigInterface::class]->get();
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
    private function initializeEventManager()
    {
        $this->make(EventManagerInterface::class, function () {
            return new EventManager($this->getContainer());
        }, true);
        $this->alias('eventmanager',EventManagerInterface::class);
    }

    /**
     * Register all externally declared service events.
     *
\     * @throws \Jshannon63\Cobalt\ContainerException
     * @throws \Jshannon63\Cobalt\NotFoundException
     */
    private function registerEvents()
    {
        $this->registerClasses(config('app.events'), function($event){
            $this->bind(strtolower('App.Events.'.$event['basename']), $event['classname'], true);
            $this[strtolower('App.Events.'.$event['basename'])]->setName(strtolower('App.Events.'.$event['basename']));
        }, \Adept\Event\Event::class);
    }

    /**
     * Register all externally declared service listeners.
     *
     * @throws \Jshannon63\Cobalt\ContainerException
     * @throws \Jshannon63\Cobalt\NotFoundException
     */
    private function registerListeners()
    {
        $this->registerClasses(config('app.listeners'), function($listener){
            $this->bind(strtolower('App.Listeners.'.$listener['basename']), $listener['classname'], true);
        }, \Adept\Event\Listener::class);
    }

    /**
     * Register all externally declared service providers.
     *
     * @throws \Jshannon63\Cobalt\ContainerException
     */
    private function registerProviders()
    {
        $this->registerClasses(config('app.providers'),function($provider){
            $this->bind($provider['classname'], $provider['classname'], true);
            $this->alias($provider['basename'], $provider['classname']);
            $this[$provider['basename']]->register($this);
            $this->providers[] = $provider['basename'];
        }, ServiceProvider::class);
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
    private function intializeRouter()
    {
        $this->bind(RouterInterface::class, function () {
            return new Router(app(), config('app.router'));
        }, true);
        $this->alias('router', RouterInterface::class);
        $this->router = $this['router'];
    }

    /**
     * Register Route Files
     *
     * @throws \Jshannon63\Cobalt\ContainerException
     * @throws \Jshannon63\Cobalt\NotFoundException
     */
    private function registerRoutes()
    {
        foreach (config('app.routes') as $directory){
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
    }

    /**
     * Register externally declared controllers
     *
     * @param $directory
     * @throws \Jshannon63\Cobalt\ContainerException
     * @throws \Jshannon63\Cobalt\NotFoundException
     */
    private function registerControllers()
    {
        $this->registerClasses(config('app.controllers'), function($controller){
            $this->bind($controller['classname'], $controller['classname'], true);
            $this->alias($controller['basename'], $controller['classname']);
            $this->controllers[] = $controller['basename'];
        }, \Adept\Application\Controller::class);
    }

    /**
     * Register all externally declared commands.
     */
    private function registerCommands()
    {
        $directories = config('app.commands');
        array_unshift($directories, __DIR__.'/../Console/Commands');
        $this->registerClasses($directories, function($command){
            $this->bind($command['classname'], $command['classname'], true);
            $this->console->add($this[$command['classname']]);
        }, \Adept\Console\Command::class);
    }

    /**
     * Register and fire-if-due, all externally declared crons.
     */
    private function registerCrons()
    {
        $this->registerClasses(config('app.crons'), function($cron){
            (new $cron['classname'])->process();
        }, \Adept\Console\Cron::class);
    }

    /**
     * Register all externally declared facades.
     */
    private function registerFacades()
    {
        $this->registerClasses([__DIR__.'/Facades'],null, Facade::class);
    }

    /**
     * @param $directories
     * @param callable|null $callback
     * @param null $parent
     * @throws Exception
     */
    private function registerClasses($directories, callable $callback=null, $parent=null)
    {
        foreach ($directories as $directory){
            $classes = getClassFiles($directory);
            foreach ($classes as $class) {
                if($class['classname'] == $parent){
                    continue;
                }
                if (! class_exists($class['classname'])) {
                    require_once $class['pathname'];
                }
                if($parent) {
                    if (get_parent_class($class['classname']) != $parent) {
                        throw new Exception('Attempt to register '.$class['classname'].
                            ' failed. Class must extend '.$parent);
                    }
                }
                if(is_callable($callback)) {
                    $callback($class);
                }
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
