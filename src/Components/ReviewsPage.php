<?php

namespace Peers\Components;

use Bottledcode\DurablePhp\DurableClientInterface;
use Bottledcode\DurablePhp\State\Ids\StateId;
use Bottledcode\SwytchFramework\CacheControl\Queue;
use Bottledcode\SwytchFramework\Hooks\Common\Headers;
use Bottledcode\SwytchFramework\Hooks\Html\HeadTagFilter;
use Bottledcode\SwytchFramework\Router\Attributes\Route;
use Bottledcode\SwytchFramework\Router\Method;
use Bottledcode\SwytchFramework\Template\Attributes\Authenticated;
use Bottledcode\SwytchFramework\Template\Attributes\Component;
use Bottledcode\SwytchFramework\Template\Compiler;
use Bottledcode\SwytchFramework\Template\Traits\Htmx;
use Bottledcode\SwytchFramework\Template\Traits\RegularPHP;
use Peers\Authentication;
use Peers\CacheRenderer;
use Peers\ClerkUser;
use Peers\Model\Interfaces\User;
use Withinboredom\ResponseCode\HttpResponseCode;

#[Component('ReviewsPage')]
#[Authenticated(visible: true)]
class ReviewsPage
{
    use RegularPHP;
    use Htmx;
    use CacheRenderer;

    public function __construct(
        private readonly Authentication         $authentication,
        private readonly Headers                $headers,
        private readonly DurableClientInterface $client,
        private readonly HeadTagFilter          $htmlHeaders,
        private readonly Compiler               $compiler,
        private readonly Queue                  $cacheControl,
    )
    {
    }

    #[Route(Method::POST, '/api/read/:round/:number')]
    public function read(int $round, int $number): string
    {
        $user = $this->authentication->getUser();
        if ($user === null) {
            $this->headers->setHeader('redirect', 'Location: /', true);
            \Withinboredom\ResponseCode\http_response_code(HttpResponseCode::TemporaryRedirect);
            return '<div></div>';
        }

        /**
         * @var User $snapshot
         */
        $snapshot = $this->client->getEntitySnapshot(StateId::fromString($user->externalId)->toEntityId());

        $message = $snapshot->getPublishedReviews();
        $message = $message[$number - 1] ?? null;

        $this->begin();
        if ($message === null) {
            ?>
            <div class="text-sm leading-6" hx-swap-oob="true"
                 id="open-<?= $round ?>-<?= -$number ?>">
                <button
                        hx-post="/api/read/<?= $round ?>/<?= -$number ?>"
                        hx-target="next text"
                >
                    🔽 <?= __('Open') ?>
                </button>
            </div>
            <div class=""></div>
            <?php
            return $this->html($this->end());
        }

        ?>
        <div class="text-sm leading-6" hx-swap-oob="true" id="open-<?= $round ?>-<?= $number ?>">
            <button
                    hx-post="/api/read/<?= $round ?>/<?= -$number ?>"
                    hx-target="next text"
            >
                🔼 <?= __('Close') ?>
            </button>
        </div>
        <div class="prose dark:prose-invert lg:prose-xl">
            <div class="text-lg" id="r-<?= $round ?>-<?= $number ?>"></div>
            <script>
                read('r-<?= $round ?>-<?= $number ?>', '{<?= $message->reviewText ?>}');
            </script>
        </div>
        <?php
        return $this->html($this->end());
    }

    public function test(ClerkUser $user)
    {
        return $user->with(externalId: 'test');
    }

    public function render(): string
    {
        $user = $this->authentication->getUser();
        if ($user === null) {
            $this->htmlHeaders->addLines('reload', '<meta http-equiv="refresh" content="2; url=/reviews">');
            return '<div>redirect</div>';
        }

        $this->htmlHeaders->addCss('snow-theme', '/assets/quill.snow.css');
        $this->htmlHeaders->addCss('snow-disabled', '/assets/quill.snow.disabled.css');

        /**
         * @var User $snapshot
         */
        $snapshot = $this->client->getEntitySnapshot(StateId::fromString($user->externalId)->toEntityId());
        if ($snapshot === null) {
            $this->htmlHeaders->addLines('reload', '<meta http-equiv="refresh" content="2; url=/reviews">');
            $this->begin();
            ?>
            <body class="dark:text-stone-50 text-stone-900 bg-stone-50 dark:bg-stone-800">
            <div class="flex justify-center items-center min-h-screen">
                <!-- loading animation -->
                <div class="h-[25vh] w-[25vw] md:w-16 md:h-16 transition ease-in-out duration-150">
                    <svg style="animation: spin 1s linear infinite;"
                         class="animate-spin -ml-1 mr-3 md:h-16 md:w-16 h-[25vh] w-[25vw] text-white"
                         xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor"
                              d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </div>
            </div>
            </body>
            <?php
            return $this->end();
        }

        $userId = StateId::fromString($user->externalId)->toEntityId();
        $nextReviewLink = "https://" . $_SERVER['HTTP_HOST'] . "/review/{$userId->id}/{$snapshot->getNextRound()}";

        ['pending' => $pending, 'published' => $published] = $snapshot->getReviewCount();

        $this->begin();
        ?>
        <body class="min-h-screen bg-stone-50 dark:bg-stone-800 dark:text-stone-50 text-stone-900">
        <UserHeader
                firstName="{<?= $user->firstName ?>}"
                lastName="{<?= $user->lastName ?>}"
                imageUrl="{<?= $user->imageUrl ?>}"
                isMe
        >
        </UserHeader>
        <div class="min-h-full flex justify-center flex-col items-center">
            <?php if (!$published || ($published && !$pending)): ?>
                <div class="py-4">
                    <?= __("Want to receive some reviews? Share this link with your peers:") ?>
                </div>
                <div class="py-4">
                    <input class="rounded-md w-80 bg-stone-50 dark:bg-stone-800" type="text" readonly
                           value="<?= $nextReviewLink ?>" id="copyme">
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
                <div class="pt-1 hidden" id="copied">
                    <span>copied to clipboard</span>
                </div>
            <?php endif; ?>
        </div>
        <?php if ($pending > 0): ?>
            <div class="flex justify-center flex-col items-center">
                <div class="pt-4">
                    <?= n__("You have a pending review", "You have pending reviews", $pending) ?>
                </div>
                <span class="py-0 text-sm font-light">
                <?= __('(reviews are intentionally delayed to protect the privacy of the reviewer)') ?>
            </span>
            </div>
        <?php endif; ?>
        <?php if ($published > 0): ?>
            <div class="px-4 md:grid md:grid-cols-10">
                <ul role="list" class="md:col-start-2 md:col-span-8 divide-y divide-stone-500">
                    <?php
                    foreach ($snapshot->getPublishedReviews() as $review):
                        ?>
                        <li class="grid grid-cols-2 justify-between gap-x-6 py-5"
                            id="review-<?= $review->round ?>-<?= $review->number ?>">
                            <div class="flex gap-x-4">
                                <div class="min-w-0 flex-auto">
                                    <p class="text-sm font-semibold leading-6"><?= n__('Round: %s', 'Round: %s', $review->round, $review->round) ?></p>
                                    <p class="mt-1 truncate text-xs leading-5"><?= n__('Review number: %s', 'Review number: %s', $review->number, $review->number) ?></p>
                                </div>
                            </div>
                            <div class="sm:flex sm:flex-col sm:items-end">
                                <div class="text-sm leading-6" id="open-<?= $review->round ?>-<?= $review->number ?>">
                                    <button
                                            hx-post="/api/read/<?= $review->round ?>/<?= $review->number ?>"
                                            hx-target="next text"
                                    >
                                        🔽 <?= __('Open') ?>
                                    </button>
                                </div>
                                <p class="mt-1 text-xs leading-5"
                                   title="<?= $review->createdAt->format(DATE_RFC822) ?>">
                                    <?= __('Published %s ago', '<time datetime="' . $review->createdAt->format(DATE_RFC3339) . '">' . $this->someTimeAgo($review->createdAt) . '</time>') ?>
                                </p>
                            </div>
                            <Text class="col-span-2">
                            </Text>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <div class="px-4 flex justify-center items-center">
                <p>
                    <?= __('Discover any issues or have any questions? Email %1$s support %2$s', '<a href="mailto:support@bottled.codes">', '</a>') ?>
                </p>
            </div>
            <script src="/assets/quill.js"></script>
            <script src="/assets/reader.js"></script>
        <?php endif; ?>
        </body>
        <?php
        return $this->end();
    }

    private function someTimeAgo(\DateTimeImmutable $time): string
    {
        $now = new \DateTimeImmutable();
        $diff = $now->diff($time);
        $weeks = floor($diff->d / 7);
        $days = $diff->d % 7;

        $string = array(
            'y' => p__('time-years', 'y'),
            'm' => p__('time-month', 'm'),
            'w' => p__('time-week', 'w'),
            'd' => p__('time-day', 'd'),
            'h' => p__('time-hour', 'h'),
            'i' => p__('time-minute', 'm'),
            's' => p__('time-second', 's'),
        );

        $time = [];

        foreach ($string as $k => $v) {
            $value = match ($k) {
                'w' => $weeks,
                'd' => $days,
                default => $diff->$k,
            };
            if ($value) {
                $time[] = $value . $v;
            }
        }

        return $time[0] ?? 'now';
    }
}
