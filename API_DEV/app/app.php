<?php

use Silex\Application;

$app = new Application();
$app['app.name'] = 'APIRoche';

return $app;