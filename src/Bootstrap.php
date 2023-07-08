<?php

use Bottledcode\DurablePhp\Abstractions\Sources\Source;
use Bottledcode\DurablePhp\Abstractions\Sources\SourceFactory;
use Bottledcode\DurablePhp\Config\Config;
use Bottledcode\DurablePhp\Config\RethinkDbConfig;
use Bottledcode\DurablePhp\DurableClient;
use Bottledcode\DurablePhp\DurableClientInterface;
use Bottledcode\DurablePhp\EntityClient;
use Bottledcode\DurablePhp\EntityClientInterface;
use Bottledcode\DurablePhp\OrchestrationClient;
use Bottledcode\DurablePhp\OrchestrationClientInterface;
use Bottledcode\DurablePhp\Processor;
use Bottledcode\SwytchFramework\Template\Interfaces\AuthenticationServiceInterface;
use DI\ContainerBuilder;
use DI\FactoryInterface;
use Peers\Authentication;
use Peers\Model\Interfaces\User;
use function Withinboredom\NameOf\nameof;

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
            bootstrapPath: __DIR__ . '/../src/Bootstrap.php',
            maximumMemoryPerWorker: 128,
            factory: 'make'
        ),
        Source::class => fn(Config $config) => SourceFactory::fromConfig($config),
        AuthenticationServiceInterface::class => \DI\autowire(Authentication::class),
        DurableClientInterface::class => \DI\autowire(DurableClient::class),
        EntityClientInterface::class => \DI\autowire(EntityClient::class),
        OrchestrationClientInterface::class => \DI\autowire(OrchestrationClient::class),
        User::class => \DI\autowire(\Peers\Model\Entities\User::class),
        \Psr\Log\LoggerInterface::class => function () {
            $logger = new \Monolog\Logger('peers');
            $logger->pushHandler(new \Monolog\Handler\StreamHandler('php://stdout', \Monolog\Level::Debug));
            return $logger;
        },
        'env.SWYTCH_LANGUAGE_DIR' => __DIR__ . '/../languages/',
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
