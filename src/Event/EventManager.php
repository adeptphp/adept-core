<?php

namespace Adept\Event;

use Jshannon63\Cobalt\ContainerInterface;
use Jshannon63\Cobalt\Container;
use InvalidArgumentException;

class EventManager implements EventManagerInterface
{
    protected $container;

    public $bindings = [];

    public function __construct(ContainerInterface $container = null)
    {
        $this->container = $container ?: new Container;
    }

    /**
     * Subscribes a listener to an event
     *
     * @param string|EventInterface $event the event to attach too
     * @param callable|ListenerInterface $callback a callable function or ListenerInterface object
     * @return bool true on success false on failure
     */
    public function subscribe($event, $listener)
    {
        if ($listener instanceof ListenerInterface) {
            $this->attach($event instanceof EventInterface ? $event->getName() : $event, function () use ($event,$listener) {
                $listener->handle($event);
                // $listener->handle($event);
            });
            return true;
        }
        if (is_callable($listener)) {
            $this->attach($event instanceof EventInterface ? $event->getName() : $event, $listener);
            return true;
        }
        return false;
    }

    /**
     * Unsubscribes a listener to an event
     *
     * @param string|EventInterface $event the event to attach too
     * @param callable|ListenerInterface $callback a callable function or ListenerInterface object
     * @return bool true on success false on failure
     */
    public function unsubscribe($event, $listener)
    {
        $this->detach($event->getName(), function () use ($event,$listener) {
            $listener->handle($event);
        });
        return true;
    }

    public function broadcast($event)
    {
        $this->trigger($event);
    }

    /**
     * Attaches a listener to an event
     *
     * @param string $event the event to attach too
     * @param callable $callback a callable function
     * @param int $priority the priority at which the $callback executed
     * @return bool true on success false on failure
     */
    public function attach($event, $callback, $priority = 0)
    {
        $this->bindings[$event][spl_object_hash((object) $callback)] = $callback;
        return true;
    }

    /**
     * Detaches a listener from an event
     *
     * @param string $event the event to attach too
     * @param callable $callback a callable function
     * @return bool true on success false on failure
     */
    public function detach($event, $callback)
    {
    }

    /**
     * Clear all listeners for a given event
     *
     * @param  string $event
     * @return void
     */
    public function clearListeners($event)
    {
        unset($this->bindings[$event]);
    }

    /**
     * Trigger an event
     *
     * Can accept an EventInterface or will create one if not passed
     *
     * @param  string|EventInterface $event
     * @param  object|string $target
     * @param  array|object $argv
     * @return mixed
     * @throws \InvalidArgumentException
     */
    public function trigger($event, $target = null, $argv = [])
    {
        if ($event instanceof EventInterface) {
            if (!$this->container->has($event)) {
                if (!$event->getName()) {
                    throw new InvalidArgumentException('Supplied Event ('.get_class($event).') requires name a valid name property.');
                }
                $this->container->bind($event->getName(), function () use ($event) {
                    return $event;
                }, true);
            }
            $event = $event->getName();
        }
        if (is_string($event) && !$this->container->has($event)) {
            $this->container->bind($event, function () {
                return new Event();
            }, true);
            $this->container[$event]->setName($event);
            $this->container[$event]->setTarget($target);
            $this->container[$event]->setParams($argv);
        }
        if (isset($this->bindings[$event])) {
            foreach ($this->bindings[$event] as $listener) {
                $listener();
            }
        }
        return $this->container[$event];
    }
}
