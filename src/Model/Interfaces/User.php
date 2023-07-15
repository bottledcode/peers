<?php

namespace Peers\Model\Interfaces;

use JetBrains\PhpStorm\ArrayShape;
use Peers\Model\IO\Review;

interface User
{
    public function addReview(Review $review): void;

    public function finishRound(int $round): void;

    public function getNextRound(): int;

    public function updateName(string $firstName, string $lastName): void;

    public function updateImage(string $imageUrl): void;

    public function getFirstName(): string;

    public function getLastName(): string;

    public function getImageUrl(): string;

    /**
     * @return array<Review>
     */
    public function getPublishedReviews(): array;

    #[ArrayShape(['published' => 'int', 'pending' => 'int'])]
    public function getReviewCount(): array;
}
