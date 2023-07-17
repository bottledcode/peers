<?php

namespace Peers\Model\Orchestrations;

use Bottledcode\DurablePhp\OrchestrationContext;
use Bottledcode\DurablePhp\State\Serializer;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterval;
use Peers\Model\Interfaces\User;
use Peers\Model\IO\Review;
use Peers\Model\IO\ReviewRound;

class ReviewProcess
{
    public function __invoke(OrchestrationContext $context)
    {
        $reviewInput = Serializer::deserialize($context->getInput(), ReviewRound::class);

        $user = $context->createEntityProxy(User::class, $reviewInput->userId->toEntityId());

        $expiration = $context->createTimer($reviewInput->expiration);
        $review = $context->waitForExternalEvent('review');

        $waiting = $context->waitAny($expiration, $review);
        if ($waiting === $review) {
            // we got a review!
            $review = $review->getResult();
            $review = Serializer::deserialize($review, Review::class);

            $user->addReview($review->with(
                // ensure the round is set by us, not the client
                round: $reviewInput->round,
                // mask the created-at time to provide some privacy
                createdAt: (new CarbonImmutable($review->createdAt))->add(CarbonInterval::minutes($context->getRandomInt(0, $reviewInput->maxHoldMinutes)))));

            // see if we get another review!
            $context->continueAsNew(Serializer::serialize($reviewInput));
        }

        // the timer expired ... mark the round as finished.
        $user->finishRound($reviewInput->round);
    }
}
