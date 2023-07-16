<?php

namespace Peers\Model\IO;

use Crell\fp\Evolvable;

readonly class Review
{
    use Evolvable;

    public function __construct(public int $round, public string $reviewText, public \DateTimeImmutable $createdAt, public int $number = -1)
    {
    }
}
