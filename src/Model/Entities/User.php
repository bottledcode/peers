<?php

namespace Peers\Model\Entities;

use Bottledcode\DurablePhp\Logger;
use Bottledcode\DurablePhp\State\EntityState;
use Crell\Serde\Attributes\DictionaryField;
use Crell\Serde\Attributes\SequenceField;
use Crell\Serde\KeyType;
use JetBrains\PhpStorm\ArrayShape;
use Peers\Model\IO\Review;

class User extends EntityState implements \Peers\Model\Interfaces\User
{
    public function __construct(
        #[DictionaryField(arrayType: Review::class, keyType: KeyType::Int)]
        private array  $reviews = [],
        #[SequenceField(arrayType: 'int')]
        private array  $finishedRounds = [],
        private string $firstName = '',
        private string $lastName = '',
        private string $imageUrl = '',
    )
    {
    }

    public function addReview(Review $review): void
    {
        Logger::always('Adding review');
        $this->reviews[] = $review->with(number: count($this->reviews) + 1);
    }

    public function finishRound(int $round): void
    {
        $this->finishedRounds[] = $round;
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

    #[ArrayShape(['published' => 'int', 'pending' => 'int'])]
    public function getReviewCount(): array
    {
        $now = new \DateTimeImmutable();
        return [
            'pending' => count($this->getPendingReviews($now)),
            'published' => count($this->getPublishedReviews($now)),
        ];
    }

    private function getPendingReviews(\DateTimeImmutable $now): array
    {
        return array_filter($this->getListOfReviews(), static fn(Review $review) => $review->createdAt > $now);
    }

    public function getListOfReviews(int|null $round = null): array
    {
        $round ??= $this->getNextRound();
        return array_filter($this->reviews, static fn(Review $review) => $review->round <= $round);
    }

    public function getNextRound(): int
    {
        return empty($this->finishedRounds) ? 1 : max($this->finishedRounds) + 1;
    }

    public function getPublishedReviews(\DateTimeImmutable $now = new \DateTimeImmutable()): array
    {
        return array_reverse(array_filter($this->getListOfReviews(), static fn(Review $review) => $review->createdAt <= $now), true);
    }
}
