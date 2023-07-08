<?php

namespace Peers\Components;

use Bottledcode\DurablePhp\DurableClientInterface;
use Bottledcode\DurablePhp\State\EntityId;
use Bottledcode\DurablePhp\State\Ids\StateId;
use Bottledcode\SwytchFramework\Hooks\Common\Headers;
use Bottledcode\SwytchFramework\Hooks\Html\HeadTagFilter;
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
        private readonly Headers                $headers,
        private readonly Authentication         $authentication,
        private readonly HeadTagFilter          $headTagFilter,
    )
    {
    }

    public function render(string $userId, int $round)
    {
        // see if the user exists
        try {
            $entityId = new EntityId(User::class, $userId);
            /**
             * @var User $snapshot
             */
            $snapshot = $this->client->getEntitySnapshot($entityId);
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
        if ($loggedInUser and StateId::fromString($loggedInUser->externalId)->toEntityId()->id === $userId) {
            // we are logged in as the user we are trying to review
            // todo: show current review status
        }

        $this->begin();
        ?>
        <body class="bg-stone-50 dark:bg-stone-800 text-stone-900 dark:text-stone-50">
        <UserHeader firstName="{<?= $snapshot->getFirstName() ?>}"
                    imageUrl="{<?= $snapshot->getImageUrl() ?>}"></UserHeader>

        <form
                hx-confirm="Once submitted, it cannot be undone. Continue?"
                hx-post="/submit-review/<?= $userId ?>/<?= $round ?>"
        >
            <div class="p-5 bg-stone-50 dark:bg-stone-800">
                <div class="md:grid md:grid-cols-2 md:gap-6">
                    <div class="prose dark:prose-invert">
                        <h2><?= __("Peer review for %s", $snapshot->getFirstName()) ?></h2>
                        <p>
                            <?= __('%1$s %2$s is asking for professional feedback. Please be honest and constructive. Your review will be anonymous unless you share your name in the review text.', $snapshot->getFirstName(), $snapshot->getLastName()) ?>
                        </p>
                        <p>
                            <?= __("Feel free to schedule some 1:1 time together, if you prefer to give feedback face-to-face.") ?>
                        </p>
                    </div>
                    <input type="hidden" id="text-editor" name="text_editor" value=""/>
                    <div class="mt-5 md:col-span-2 md:mt-0">
                        <div class="shadow sm:rounded-md">
                            <div class="space-y-6 bg-white dark:bg-stone-900 px-4 py-5 sm:p-6">
                                <div class="grid grid-cols-6 gap-6">
                                    <div class="col-span-6 dark:bg-white h-fit">
                                        <div
                                                class="prose dark:prose-invert dark:bg-stone-800 w-full max-w-7xl"
                                                id="editor"
                                        >
                                            <h3>
                                                <?= /* translators: %s is a first name */
                                                __("What is something %s should continue doing?", $snapshot->getFirstName()) ?>
                                            </h3>
                                            <p>&nbsp;</p>
                                            <h3>
                                                <?= /* translators: %s is a first name */
                                                __("What is something %s should stop doing?", $snapshot->getFirstName()) ?>
                                            </h3>
                                            <p>&nbsp;</p>
                                            <h3>
                                                <?= /* translators: %s is a first name */
                                                __("What is something %s should start doing?", $snapshot->getFirstName()) ?>
                                            </h3>
                                            <p>&nbsp;</p>
                                            <h3>
                                                <?= /* translators: %s is a first name */
                                                __("Is there anything else you want %s to know?", $snapshot->getFirstName()) ?>
                                            </h3>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="mt-10 flex items-center justify-center gap-x-6">
                        <button class="rounded-md bg-indigo-600 px-3.5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600"
                                type="submit"
                        >
                            <?= __('Send it') ?>
                        </button>
                    </div>
                </div>
            </div>
        </form>

        <link rel="stylesheet" href="/assets/quill.snow.css">
        <script src="/assets/quill.js"></script>
        <script src="/assets/editor.js"></script>
        </body>
        <?php
        return $this->end();
    }
}
