<?php

namespace Peers\Components;

use Bottledcode\SwytchFramework\Hooks\Html\HeadTagFilter;
use Bottledcode\SwytchFramework\Language\LanguageAcceptor;
use Bottledcode\SwytchFramework\Router\Attributes\Route;
use Bottledcode\SwytchFramework\Router\Method;
use Bottledcode\SwytchFramework\Template\Attributes\Component;
use Bottledcode\SwytchFramework\Template\Traits\Htmx;
use Bottledcode\SwytchFramework\Template\Traits\RegularPHP;

#[Component('Index')]
class Index
{
    use RegularPHP;
    use Htmx;

    public function __construct(private LanguageAcceptor $language, private HeadTagFilter $headTagFilter)
    {
    }

    #[Route(path: '/healthz', method: Method::GET)]
    public function healthz()
    {
        return '';
    }

    public function render()
    {
        $this->headTagFilter->addCss('twcss', '/assets/web.css?v=' . CURRRENT_COMMIT);

        $apiKey = getenv('CLERK_PUBLIC_API_KEY');
        if (!str_starts_with($_SERVER['REQUEST_URI'], '/review/')) {
            $script = <<<JS
                const frontend_api = "$apiKey";

                // Create a script that will be loaded asynchronously in
                // your page.
                const script = document.createElement('script');
                script.setAttribute('data-clerk-frontend-api', frontend_api);
                script.async = true;
                script.src = `https://cdn.jsdelivr.net/npm/@clerk/clerk-js@latest/dist/clerk.browser.js`;
                script.crossOrigin = "anonymous";
                // Add a listener so you can initialize ClerkJS
                // once it's loaded.
                script.addEventListener('load', async function(){
                    await window.Clerk.load({});
                    if(Clerk.session && location.pathname === '/auth') {
                        location.href = '/reviews'
                    }
                });
                document.body.appendChild(script);
            JS;
            $script = $this->dangerous($script);
        } else {
            $script = '';
        }

        $this->begin();
        ?>
        <!Doctype html>
        <html lang="<?= $this->language->currentLanguage ?>">
        <head>
            <meta charset="UTF-8">
        </head>
        <Route path="/" method="GET">
            <HomePage></HomePage>
        </Route>
        <Route path="/reviews" method="GET">
            <ReviewsPage></ReviewsPage>
        </Route>
        <Route path="/review/:userId/:round" method="GET">
            <LeaveReview userId="{{:userId}}" round="{{:round}}"></LeaveReview>
        </Route>
        <Route path="/auth" method="GET">
            <Loading wait="2" next="/reviews"></Loading>
        </Route>
        <Route path="/logout" method="GET">
            <Loading wait="2" next="/"></Loading>
        </Route>
        <DefaultRoute>
            <ErrorPage></ErrorPage>
        </DefaultRoute>
        <script>
            <?= $script ?>
        </script>
        </html>
        <?php
        return $this->end();
    }
}
