<?php

namespace Peers\Model\IO;

use Bottledcode\DurablePhp\State\Ids\StateId;
use Crell\Serde\Attributes\DateField;
use Crell\Serde\Attributes\SequenceField;

readonly class ReviewInput
{
    public function __construct(
        public StateId $userId,

        #[DateField]
        public \DateTimeImmutable $expiration,

        public int $round,
    )
    {
    }
}
