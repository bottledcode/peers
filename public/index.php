<?php

use Bottledcode\SwytchFramework\App;
use Peers\Components\Index;

require_once __DIR__ . '/../src/Bootstrap.php';
require_once __DIR__ . '/../vendor/attributes.php';

define('CURRRENT_COMMIT', file_get_contents(__DIR__ . '/../.git/refs/heads/main'));

$app = new App(true, Index::class, getDependencies());
$app->run();
