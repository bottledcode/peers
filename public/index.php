<?php

use Bottledcode\SwytchFramework\App;
use Peers\Components\Index;

require_once __DIR__ . '/../src/Bootstrap.php';
require_once __DIR__ . '/../vendor/attributes.php';

$app = new App(true, Index::class, getDependencies());
$app->run();
