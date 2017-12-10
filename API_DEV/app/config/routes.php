<?php

$app->get('/connect','APIRoche\\APIHandler::getIdAction');
$app->post('/bot','APIRoche\\APIHandler::getBotResponse');
$app->post('/form','APIRoche\\APIHandler::getFormResults');