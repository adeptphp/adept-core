<?php

namespace Adept\Log;

use Psr\Log\AbstractLogger;
use Monolog\Logger;
use Monolog\Processor\UidProcessor;
use Monolog\Processor\PsrLogMessageProcessor;
use Monolog\Handler\StreamHandler;

class Log extends AbstractLogger implements LoggerInterface
{
    protected $logger;

    public function __construct(String $path)
    {
        $this->logger = new Logger('adept');
        $this->logger->pushProcessor(new UidProcessor());
        $this->logger->pushProcessor(new PsrLogMessageProcessor());
        $this->logger->pushHandler(new StreamHandler($path));
    }

    public function log($level, $message, array $context = [])
    {
        $this->logger->log($level, $message, $context);
    }

    public function monolog()
    {
        return $this->logger;
    }
}
