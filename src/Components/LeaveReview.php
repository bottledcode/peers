<?php

namespace Peers\Components;

use Bottledcode\DurablePhp\DurableClientInterface;
use Bottledcode\DurablePhp\State\EntityId;
use Bottledcode\DurablePhp\State\Ids\StateId;
use Bottledcode\SwytchFramework\Hooks\Common\Headers;
use Bottledcode\SwytchFramework\Template\Attributes\Component;
use Bottledcode\SwytchFramework\Template\Traits\Htmx;
use Bottledcode\SwytchFramework\Template\Traits\RegularPHP;
use Peers\Authentication;
use Peers\Model\Interfaces\User;

#[Component('LeaveReview')]
class LeaveReview
{
    use RegularPHP;
    use Htmx;

    public function __construct(
        private readonly DurableClientInterface $client,
        private readonly Headers $headers,
        private readonly Authentication $authentication
    )
    {
    }

    public function render(string $userId, int $round)
    {
        // see if the user exists
        try {
            /**
             * @var User $snapshot
             */
            $snapshot = $this->client->getEntitySnapshot(new EntityId(User::class, $userId));
        } catch (\Exception) {
            $snapshot = null;
        }
        if ($snapshot === null) {
            $this->redirectClient('/404');
            http_response_code(404);
            return "<ErrorPage></ErrorPage>";
        }

        // ok, the user exists... now see if the round is valid
        if ($snapshot->getNextRound() !== $round) {
            $this->redirectClient('/404');
            http_response_code(404);
            return "<ErrorPage></ErrorPage>";
        }

        // see if we are logged in as that user
        $loggedInUser = $this->authentication->getUser();
        if($loggedInUser and StateId::fromString($loggedInUser->externalId)->toEntityId()->id === $userId) {
            // we are logged in as the user we are trying to review
            // todo: show current review status
        }

        $this->begin();
        ?>
        <body class="bg-stone-50 ">
        <UserHeader firstName="{<?= $snapshot->getFirstName() ?>}" imageUrl="{<?= $snapshot->getImageUrl() ?>}"></UserHeader>
        </body>
        <?php
        return $this->end();
    }
}
