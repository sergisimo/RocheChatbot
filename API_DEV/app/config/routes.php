<?php

$app->get('/','APIRoche\\APIHandler::indexAction');

$app->get('/connect','APIRoche\\APIHandler::getIdAction');
$app->post('/bot','APIRoche\\APIHandler::getBotResponse');