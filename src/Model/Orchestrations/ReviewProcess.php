<?php

namespace Peers\Model\Orchestrations;

use Bottledcode\DurablePhp\OrchestrationContext;
use Bottledcode\DurablePhp\State\Serializer;
use Peers\Model\Interfaces\User;
use Peers\Model\IO\ReviewInput;

class ReviewProcess
{
    public function __invoke(OrchestrationContext $context)
    {
        $reviewInput = Serializer::deserialize($context->getInput(), ReviewInput::class);

        $user = $context->createEntityProxy(User::class, $reviewInput->userId);

        $expiration = $context->createTimer($reviewInput->expiration);
        $review = $context->waitForExternalEvent('review');

        $waiting = $context->waitAny($expiration, $review);
        if ($waiting === $review) {
            // we got a review!
            $review = $review->getResult();
            $user->addReview($review->with(round: $reviewInput->round));

            // see if we get another review!
            $context->continueAsNew(Serializer::serialize($reviewInput));
        }

        // the timer expired ... mark the round as finished.
        $user->finishRound($reviewInput->round);
    }
}
