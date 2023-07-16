<?php

namespace Peers;

use Bottledcode\SwytchFramework\CacheControl\Builder;
use Bottledcode\SwytchFramework\CacheControl\Queue;
use Bottledcode\SwytchFramework\Hooks\Common\Headers;

trait CacheRenderer
{
    public function renderCache(Headers $headers, Builder $builder, string $etag): void
    {
        if(method_exists($builder, 'render')) {
            $builder->render($etag);
        }
    }
}
