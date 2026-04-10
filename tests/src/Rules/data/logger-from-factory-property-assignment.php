<?php

declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Tests\Rules\data;

use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;

// Error: logger stored from factory->get() in constructor.
class ClassWithLoggerFromFactory
{
    private LoggerChannelInterface $logger;

    private LoggerChannelFactoryInterface $loggerFactory;

    public function __construct(LoggerChannelFactoryInterface $loggerFactory)
    {
        $this->loggerFactory = $loggerFactory;
        $this->logger = $this->loggerFactory->get('my_module'); // error on this line
    }
}

// No error: factory->get() called outside constructor.
class ClassWithLoggerFromFactoryOutsideConstructor
{
    private LoggerChannelInterface $logger;

    private LoggerChannelFactoryInterface $loggerFactory;

    public function __construct(LoggerChannelFactoryInterface $loggerFactory)
    {
        $this->loggerFactory = $loggerFactory;
    }

    public function setLogger(): void
    {
        $this->logger = $this->loggerFactory->get('my_module'); // no error
    }
}

// No error: direct service injection (not from factory->get()).
class ClassWithDirectLoggerInjection
{
    private LoggerChannelInterface $logger;

    public function __construct(LoggerChannelInterface $logger)
    {
        $this->logger = $logger; // no error
    }
}

// No error: local variable assignment from factory->get() in constructor.
class ClassWithLocalLoggerVariable
{
    private LoggerChannelFactoryInterface $loggerFactory;

    public function __construct(LoggerChannelFactoryInterface $loggerFactory)
    {
        $this->loggerFactory = $loggerFactory;
        $logger = $this->loggerFactory->get('my_module'); // no error: not a property
    }
}
