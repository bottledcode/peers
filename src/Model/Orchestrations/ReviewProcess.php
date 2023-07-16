<?php

namespace Peers\Model\Orchestrations;

use Bottledcode\DurablePhp\Logger;
use Bottledcode\DurablePhp\OrchestrationContext;
use Bottledcode\DurablePhp\State\Serializer;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterval;
use Peers\Model\Interfaces\User;
use Peers\Model\IO\Review;
use Peers\Model\IO\ReviewInput;

class ReviewProcess
{
    public function __invoke(OrchestrationContext $context)
    {
        $reviewInput = Serializer::deserialize($context->getInput(), ReviewInput::class);

        $user = $context->createEntityProxy(User::class, $reviewInput->userId->toEntityId());

        $expiration = $context->createTimer($reviewInput->expiration);
        $review = $context->waitForExternalEvent('review');

        $waiting = $context->waitAny($expiration, $review);
        if ($waiting === $review) {
            Logger::always('Review received!');
            // we got a review!
            $review = $review->getResult();
            $review = Serializer::deserialize($review, Review::class);

            Logger::always('Review: ' . $review->reviewText);

            $user->addReview($review->with(
                // ensure the round is set by us, not the client
                round: $reviewInput->round,
                // mask the created-at time to provide some privacy
                createdAt: (new CarbonImmutable($review->createdAt))->add(CarbonInterval::minutes($context->getRandomInt(0, $reviewInput->maxHoldMinutes)))));

            Logger::always('restarting...');
            // see if we get another review!
            $context->continueAsNew(Serializer::serialize($reviewInput));
        }

        // the timer expired ... mark the round as finished.
        $user->finishRound($reviewInput->round);
    }
}
