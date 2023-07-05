<?php

use Bottledcode\DurablePhp\Config\Config;
use Bottledcode\DurablePhp\Config\RethinkDbConfig;
use Bottledcode\DurablePhp\Processor;
use DI\ContainerBuilder;
use DI\FactoryInterface;

require_once __DIR__ . '/../vendor/autoload.php';

// global functions

function make(string $className)
{
    static $factory;
    $factory ??= getFactory();
    return $factory->make($className);
}

function getDependencies(): array
{
    return [
        RethinkDbConfig::class => fn() => new RethinkDbConfig(
            host: getenv('RETHINKDB_HOST') ?: 'localhost',
            username: getenv('RETHINKDB_USER') ?: 'admin',
            password: getenv('RETHINKDB_PASSWORD') ?: '',
            database: getenv('RETHINKDB_DATABASE') ?: 'peers',
        ),
        Config::class => fn(RethinkDbConfig $config) => new Config(
            currentPartition: (int)array_slice(explode('-', gethostname()), -1)[0],
            storageConfig: $config,
            totalPartitions: (int)getenv('TOTAL_PARTITIONS') ?: 3,
            totalWorkers: (int)getenv('TOTAL_WORKERS') ?: 32,
            bootstrapPath: __FILE__,
            maximumMemoryPerWorker: 128,
            factory: 'make'
        ),
        \Bottledcode\SwytchFramework\Template\Interfaces\AuthenticationServiceInterface::class => \DI\autowire(\Peers\Authentication::class)
    ];
}

function getFactory(): FactoryInterface
{
    static $factory = null;

    if ($factory !== null) {
        return $factory;
    }

    $container = new ContainerBuilder();
    $container->useAttributes(true)->useAutowiring(true)->enableDefinitionCache()->enableCompilation(__DIR__ . '/../.compilation');

    $container->addDefinitions(getDependencies());

    return $factory = $container->build();
}

function startup(): never
{
    $factory = getFactory();
    $processor = $factory->make(Processor::class);
    $processor();

    exit(1);
}
