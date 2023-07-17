<?php

namespace Peers\Model\IO;

use Bottledcode\DurablePhp\State\Ids\StateId;
use Crell\Serde\Attributes\DateField;

readonly class ReviewRound
{
    public function __construct(
        public StateId $userId,

        public int $round,

        #[DateField]
        public \DateTimeImmutable $expiration = new \DateTimeImmutable('+7 days'),

        public int $maxHoldMinutes = 120,
    )
    {
    }
}
