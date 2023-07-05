<?php

namespace Peers\Model\Entities;

use Bottledcode\DurablePhp\State\EntityState;
use Crell\Serde\Attributes\DictionaryField;
use Crell\Serde\Attributes\SequenceField;
use Crell\Serde\KeyType;
use Peers\Model\IO\Review;

class User extends EntityState implements \Peers\Model\Interfaces\User
{
    public function __construct(
        #[DictionaryField(arrayType: Review::class, keyType: KeyType::Int)]
        private array $reviews = [],
        #[SequenceField(arrayType: 'int')]
        private array $finishedRounds = [],
        private string $firstName = '',
        private string $lastName = '',
        private string $imageUrl = '',
    )
    {
    }

    public function getListOfReviews(int|null $round = null): array
    {
        return $round ? $this->reviews[$round] : $this->reviews;
    }

    public function addReview(Review $review): void
    {
        $this->reviews[$review->round][] = $review;
    }

    public function finishRound(int $round): void
    {
        $this->finishedRounds[] = $round;
    }

    public function getNextRound(): int
    {
        return max($this->finishedRounds) + 1;
    }

    public function updateName(string $firstName, string $lastName): void
    {
        $this->firstName = $firstName;
        $this->lastName = $lastName;
    }

    public function updateImage(string $imageUrl): void
    {
        $this->imageUrl = $imageUrl;
    }

    public function getFirstName(): string
    {
        return $this->firstName;
    }

    public function getLastName(): string
    {
        return $this->lastName;
    }

    public function getImageUrl(): string
    {
        return $this->imageUrl;
    }
}
