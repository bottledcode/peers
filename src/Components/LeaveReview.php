<?php

namespace Peers\Components;

use Bottledcode\DurablePhp\DurableClientInterface;
use Bottledcode\DurablePhp\State\EntityId;
use Bottledcode\DurablePhp\State\Ids\StateId;
use Bottledcode\DurablePhp\State\OrchestrationInstance;
use Bottledcode\DurablePhp\State\RuntimeStatus;
use Bottledcode\DurablePhp\State\Serializer;
use Bottledcode\SwytchFramework\Hooks\Common\Headers;
use Bottledcode\SwytchFramework\Hooks\Html\HeadTagFilter;
use Bottledcode\SwytchFramework\Router\Attributes\Route;
use Bottledcode\SwytchFramework\Router\Method;
use Bottledcode\SwytchFramework\Template\Attributes\Component;
use Bottledcode\SwytchFramework\Template\Traits\Htmx;
use Bottledcode\SwytchFramework\Template\Traits\RegularPHP;
use Peers\Authentication;
use Peers\Model\Interfaces\User;
use Peers\Model\IO\Review;
use Peers\Model\IO\ReviewRound;
use Peers\Model\Orchestrations\ReviewProcess;

#[Component('LeaveReview')]
class LeaveReview
{
    use RegularPHP;
    use Htmx;

    public function __construct(
        private readonly DurableClientInterface $client,
        private readonly Headers $headers,
        private readonly Authentication $authentication,
        private readonly HeadTagFilter $headTagFilter,
    )
    {
    }

    #[Route(Method::PUT, '/api/review/:userId/:round')]
    public function submit(string $userId, int $round, array $state, string $text_editor): string
    {
        // check that the user exists
        try {
            $entityId = new EntityId(User::class, $userId);
            $snapshot = $this->client->getEntitySnapshot($entityId);
        } catch (\Exception) {
            $snapshot = null;
        }

        if ($snapshot === null) {
            return "<ErrorPage></ErrorPage>";
        }

        $review = new Review($round, $text_editor, new \DateTimeImmutable());

        // send the review to the orchestration
        $this->client->raiseEvent(
            new OrchestrationInstance(ReviewProcess::class, $userId . '-' . $round),
            'review',
            Serializer::serialize($review)
        );

        $this->begin();
        ?>
        <div class="mx-auto max-w-2xl prose-lg">
            <div class="text-center w-full">
                <h1 class="pl-16">Thank you!</h1>
                <p class="text-xl pl-16">Your review has been submitted. You may now close this tab.</p>
            </div>
        </div>
        <?php
        return $this->end();
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

        $this->headTagFilter->setOpenGraph(
            pageUrl: $this->currentUri(),
            title: 'Peer Reviews',
            description: 'Leave a review for ' . $snapshot->getFirstName() . ' ' . $snapshot->getLastName() . '.',
            imageUrl: $snapshot->getImageUrl(),
            locale: 'en_US'
        );

        $this->headTagFilter->setTitle('Review ' . $snapshot->getFirstName() . ' ' . $snapshot->getLastName());

        // send the round to the orchestration if it is valid
        $status = $this->client->getStatus(new OrchestrationInstance(ReviewProcess::class, $userId . '-' . $round));
        match ($status->runtimeStatus) {
            // start up an orchestration if it doesn't exist
            RuntimeStatus::Unknown => $this->client->startNew(
                ReviewProcess::class,
                Serializer::serialize(new ReviewRound(StateId::fromEntityId($entityId), $round)),
                $userId . '-' . $round),
            // there are lots of failures in development, so just purge and restart
            RuntimeStatus::Failed => $this->client->purge(new OrchestrationInstance(ReviewProcess::class, $userId . '-' . $round)) || $this->client->startNew(
                    ReviewProcess::class,
                    Serializer::serialize(new ReviewRound(StateId::fromEntityId($entityId), $round)),
                    $userId . '-' . $round),
            default => null,
        };

        $this->begin();
        ?>
        <body class="bg-stone-50 dark:bg-stone-800 text-stone-900 dark:text-stone-50">
        <UserHeader firstName="{<?= $snapshot->getFirstName() ?>}"
                    imageUrl="{<?= $snapshot->getImageUrl() ?>}"></UserHeader>

        <form
                hx-confirm="Once submitted, it cannot be undone. Continue?"
                hx-put="/api/review/<?= $userId ?>/<?= $round ?>"
        >
            <input type="hidden" id="text-editor" name="text_editor" value=""/>
            <div class="p-5 bg-stone-50 dark:bg-stone-800">
                <div class="md:grid md:grid-cols-3 md:gap-6">
                    <div class="prose dark:prose-invert">
                        <h2><?= __("Peer review for %s", $snapshot->getFirstName()) ?></h2>
                        <p>
                            <?= __('%1$s %2$s is asking for professional feedback. Please be honest and constructive. Your review will be anonymous unless you share your name in the review text.', $snapshot->getFirstName(), $snapshot->getLastName()) ?>
                        </p>
                        <p>
                            <?= __("Feel free to schedule some 1:1 time together, if you prefer to give feedback face-to-face.") ?>
                        </p>
                        <p class="pt-2 text-stone-600">
                            <?= __("This page is not tracked. There are no cookies. The IP address is not logged.") ?>
                        </p>
                    </div>
                    <div class="mt-5 md:mt-0 md:col-span-2">
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
                    <div class="md:col-span-1"></div>
                    <div class="mt-10 md:col-span-2 flex items-center justify-center gap-x-6">
                        <button class="rounded-md bg-indigo-600 dark:bg-indigo-500 px-3.5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 dark:hover:bg-indigo-600 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 dark:focus-visible:outline-indigo-500 focus-visible:outline-indigo-600"
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

    private function currentUri(): string
    {
        return 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    }
}
