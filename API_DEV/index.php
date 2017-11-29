<?php

ini_set('display_errors', 0);

require_once __DIR__.'/vendor/autoload.php';

$app = require __DIR__.'/app/app.php';
$app['debug'] = false;

require __DIR__.'/app/config/prod.php';
require __DIR__.'/app/config/routes.php';

$app->run();