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
                        <a href="https://github.com/bottledcode/peers"
                           class="absolute right-0 top-0 h-10 w-10">
                            <!-- https://github.com/tholman/github-corners/blob/master/svg/github-corner-right.svg -->
                            <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    width="80"
                                    height="80"
                                    viewBox="0 0 250 250"
                                    fill="#151513"
                                    style="position: absolute; top: 0; right: 0"
                            >
                                <path d="M0 0l115 115h15l12 27 108 108V0z" fill="#fff"/>
                                <path class="octo-arm"
                                      d="M128 109c-15-9-9-19-9-19 3-7 2-11 2-11-1-7 3-2 3-2 4 5 2 11 2 11-3 10 5 15 9 16"
                                      style="-webkit-transform-origin: 130px 106px; transform-origin: 130px 106px"/>
                                <path class="octo-body"
                                      d="M115 115s4 2 5 0l14-14c3-2 6-3 8-3-8-11-15-24 2-41 5-5 10-7 16-7 1-2 3-7 12-11 0 0 5 3 7 16 4 2 8 5 12 9s7 8 9 12c14 3 17 7 17 7-4 8-9 11-11 11 0 6-2 11-7 16-16 16-30 10-41 2 0 3-1 7-5 11l-12 11c-1 1 1 5 1 5z"/>
                            </svg>
                            <div class="transform rotate-45 text-sm origin-top-left w-80 pt-6">
                            Fork Me!
                            </div>
                        </a>
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
