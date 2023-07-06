<?php

namespace Peers\Components;

use Bottledcode\SwytchFramework\Template\Attributes\Authenticated;
use Bottledcode\SwytchFramework\Template\Attributes\Component;
use Peers\Authentication;

#[Component('LoggedInHomePage')]
#[Authenticated(visible: true)]
class LoggedInHomePage
{
    public function __construct(private Authentication $authentication)
    {
    }

    public function render()
    {
        $user = $this->authentication->getUser();
        $user = print_r($user, true);
        return <<<HTML
<pre>
$user
</pre>
HTML;

    }
}
