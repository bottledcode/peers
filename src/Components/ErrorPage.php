<?php

namespace Peers\Components;

use Bottledcode\SwytchFramework\Hooks\Html\HeadTagFilter;
use Bottledcode\SwytchFramework\Template\Attributes\Component;
use Bottledcode\SwytchFramework\Template\Traits\RegularPHP;

#[Component('ErrorPage')]
class ErrorPage
{
    use RegularPHP;

    public function __construct(private readonly HeadTagFilter $headTagFilter)
    {
    }

    public function render()
    {
        $this->headTagFilter->setTitle(__('Page not found'));

        $this->begin();
        ?>
        <body class="h-full bg-stone-50 dark:bg-stone-800 text-stone-900 dark:text-stone-50">
        <main class="grid min-h-full place-items-center px-6 py-24 sm:py-32 lg:px-8">
            <div class="text-center">
                <p class="text-base font-semibold text-indigo-600 dark:text-indigo-300"><?= __('404') ?></p>
                <h1 class="mt-4 text-3xl font-bold tracking-tight dark:text-stone-50 sm:text-5xl"><?= __('Page not found') ?></h1>
                <p class="mt-6 text-base leading-7 text-stone-600 dark:text-stone-100">
                    <?= __("Sorry, we couldn’t find the page you’re looking for.") ?></p>
                <div class="mt-10 flex items-center justify-center gap-x-6">
                    <a href="/"
                       class="rounded-md bg-indigo-600 px-3.5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
                        <?= __('Go back home') ?>
                    </a>
                </div>
            </div>
        </main>
        </body>
        <?php
        return $this->end();
    }
}
