<?php

namespace Peers\Model\IO;

use Bottledcode\DurablePhp\State\Ids\StateId;
use Crell\Serde\Attributes\SequenceField;

readonly class ReviewInput
{
    public function __construct(
        public StateId $userId,

        public \DateTimeInterface $expiration,

        public int $round,

        public int $numberExpectedReviews,
    )
    {
    }
}
