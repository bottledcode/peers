<?php

namespace Peers\Model\IO;

use Bottledcode\DurablePhp\State\Ids\StateId;
use Crell\Serde\Attributes\DateField;
use Crell\Serde\Attributes\SequenceField;

readonly class ReviewInput
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
