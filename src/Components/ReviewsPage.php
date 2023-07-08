<?php

namespace Peers\Components;

use Bottledcode\DurablePhp\DurableClientInterface;
use Bottledcode\DurablePhp\State\Ids\StateId;
use Bottledcode\SwytchFramework\Hooks\Common\Headers;
use Bottledcode\SwytchFramework\Template\Attributes\Authenticated;
use Bottledcode\SwytchFramework\Template\Attributes\Component;
use Bottledcode\SwytchFramework\Template\Traits\Htmx;
use Bottledcode\SwytchFramework\Template\Traits\RegularPHP;
use Peers\Authentication;
use Peers\Model\Interfaces\User;

#[Component('ReviewsPage')]
#[Authenticated(visible: true)]
class ReviewsPage
{
    use RegularPHP;
    use Htmx;

    public function __construct(
        private readonly Authentication $authentication,
        private readonly Headers $headers,
        private readonly DurableClientInterface $client
    )
    {
    }

    public function render(): string
    {
        $user = $this->authentication->getUser();
        if ($user === null) {
            $this->redirectClient('/');
            return '';
        }

        /**
         * @var User $snapshot
         */
        $snapshot = $this->client->getEntitySnapshot(StateId::fromString($user->externalId)->toEntityId());

        $userId = StateId::fromString($user->externalId)->toEntityId();
        $nextReviewLink = "https://peers.bottled.codes/review/{$userId->id}/{$snapshot->getNextRound()}";

        $this->begin();
        ?>
        <body class="min-h-screen bg-stone-50 text-stone-900">
        <UserHeader
                firstName="{<?= $user->firstName ?>}"
                lastName="{<?= $user->lastName ?>}"
                imageUrl="{<?= $user->imageUrl ?>}"
                isMe
        >
        </UserHeader>
        <div class="bg-stone-50 min-h-full flex justify-center flex-col items-center">
            <?php if (empty($snapshot->getListOfReviews()) and $snapshot->getNextRound() === 1): ?>
                <div class="py-4">
                    <?= __("Want to receive some reviews? Share this link with your peers:") ?>
                </div>
                <div class="py-4">
                    <input class="rounded-md w-80" type="text" readonly value="<?= $nextReviewLink ?>" id="copyme">
                    <script>
                        <?= $this->dangerous(<<<JS
                        document.getElementById('copyme').addEventListener('click', (event) => {
                            event.preventDefault();
                            navigator.clipboard.writeText(event.target.value);
                            document.getElementById('copied').classList.remove('hidden');
                            setTimeout(function () {
                                document.getElementById('copied').classList.add('hidden');
                            }, 2000);
                        });
JS
                        ); ?>
                    </script>
                </div>
                <div class="py-4 hidden" id="copied">
                    <span>copied to clipboard</span>
                </div>
            <?php endif; ?>
        </div>
        </body>
        <?php
        return $this->end();
    }
}
