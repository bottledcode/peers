<?php

namespace Peers\Components;

use Bottledcode\SwytchFramework\Hooks\Html\HeadTagFilter;
use Bottledcode\SwytchFramework\Template\Attributes\Component;
use Bottledcode\SwytchFramework\Template\Traits\RegularPHP;

#[Component('UserHeader')]
class UserHeader
{
    use RegularPHP;

    public function __construct(private readonly HeadTagFilter $htmlHeaders)
    {
    }

    public function render(string $firstName, string $lastName = '', string $imageUrl = '', bool $isMe = false): string
    {
        $this->htmlHeaders->addScript('logout', '/assets/logout.js', true, true);

        $this->begin();
        ?>
        <div class="bg-stone-100 text-stone-900 dark:bg-stone-800 dark:text-stone-50">
            <div class="mx-auto max-w-2xl px-3 py-4">
                <div>
                    <div class="flex items-start">
                        <?php if (!empty($imageUrl)): ?>
                            <div class="shrink">
                                <div class="relative">
                                    <?php if ($isMe): ?>
                                    <a href="#" onclick="Clerk.redirectToUserProfile()">
                                        <?php endif; ?>
                                        <img class="rounded-full w-16 h-16" src="<?= $imageUrl ?>" alt="avatar">
                                        <span class="ring-1 ring-inset rounded-full absolute inset-0"
                                              aria-hidden="true"></span>
                                        <?php if ($isMe): ?>
                                    </a>
                                <?php endif; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                        <div class="pt-2 mx-auto">
                            <h1 class="font-bold text-4xl"><?= $firstName ?> <?= $lastName ?></h1>
                        </div>
                        <?php if ($isMe): ?>
                            <div>
                                <a href="#" onclick="logout()"><?= __('Log out') ?></a>
                            </div>
                        <?php endif; ?>
                    </div>
                    <?php if (false): ?>
                        <div class="justify-stretch column-reverse flex mt-4">
                            <button type="button"
                                    class="ring-1 ring-inset ring-gray-900/10 font-semibold py-1 px-3 bg-white rounded-md justify-center items-center inline-flex focus:ring-2 focus:ring-purple-200">
                                <?= __('Request A Review') ?>
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php
        return $this->end();
    }
}
