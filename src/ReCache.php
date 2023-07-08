<?php

namespace Peers;

use Bottledcode\DurablePhp\Abstractions\Sources\Source;
use Closure;

class ReCache
{
    public function __construct(private readonly Source $source)
    {
    }

    /**
     * @template T
     * @param string $key
     * @param Closure<T> $value
     * @return T
     */
    public function getOrSet(string $key, Closure $value): mixed
    {
        return $this->get($key, 'array') ?? $this->put($key, $value());
    }

    /**
     * @template T
     * @param string $key
     * @param class-string<T> $type
     * @return <T>
     */
    public function get(string $key, string $type): mixed
    {
        return $this->source->get($key, $type);
    }

    /**
     * @template T
     * @param string $key
     * @param T $value
     * @return T
     */
    public function put(string $key, mixed $value): mixed
    {
        $this->source->put($key, $value);
        return $value;
    }
}
