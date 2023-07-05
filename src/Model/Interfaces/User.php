<?php

namespace Peers\Model\Interfaces;

use Peers\Model\IO\Review;

interface User
{
    /**
     * @return array<Review>
     */
    public function getListOfReviews(int|null $round = null): array;

    public function addReview(Review $review): void;

    public function finishRound(int $round): void;

    public function getNextRound(): int;

    public function updateName(string $firstName, string $lastName): void;
    public function updateImage(string $imageUrl): void;

    public function getFirstName(): string;
    public function getLastName(): string;
    public function getImageUrl(): string;
}
