<?php

namespace Peers\Components;

use Bottledcode\SwytchFramework\Hooks\Common\Headers;
use Bottledcode\SwytchFramework\Hooks\Html\HeadTagFilter;
use Bottledcode\SwytchFramework\Template\Attributes\Authenticated;
use Bottledcode\SwytchFramework\Template\Attributes\Component;
use Bottledcode\SwytchFramework\Template\Traits\Htmx;
use Peers\Authentication;
use Withinboredom\ResponseCode\HttpResponseCode;
use function Withinboredom\ResponseCode\http_response_code;

#[Component('LoggedInHomePage')]
#[Authenticated(visible: true)]
class LoggedInHomePage
{
    use Htmx;

    public function __construct(private readonly Authentication $authentication, private readonly Headers $headers, private readonly HeadTagFilter $htmlHeaders)
    {
    }

    public function render(): string
    {
        http_response_code(HttpResponseCode::Found);
        $this->headers->setHeader('Location', '/reviews');
        $this->htmlHeaders->addLines('refresh', '<meta http-equiv="refresh" content="0; url=/reviews" />');
        return <<<HTML
<div></div>
HTML;

    }
}
