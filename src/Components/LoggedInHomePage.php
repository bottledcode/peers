<?php

namespace Peers\Components;

use Bottledcode\SwytchFramework\Hooks\Common\Headers;
use Bottledcode\SwytchFramework\Template\Attributes\Authenticated;
use Bottledcode\SwytchFramework\Template\Attributes\Component;
use Bottledcode\SwytchFramework\Template\Traits\Htmx;
use Peers\Authentication;

#[Component('LoggedInHomePage')]
#[Authenticated(visible: true)]
class LoggedInHomePage
{
    use Htmx;

    public function __construct(private readonly Authentication $authentication, private readonly Headers $headers)
    {
    }

    public function render()
    {
        $this->redirectClient('/reviews');
        $user = $this->authentication->getUser();
        //$user = print_r($user, true);
        return <<<HTML
HTML;

    }
}
