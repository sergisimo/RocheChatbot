<?php

namespace APIRoche;

use Silex\Application;

use Symfony\Component\HttpFoundation\JsonResponse;

class APIHandler {

    public function indexAction(Application $app) {

        $response = new JsonResponse();

        $response->setStatusCode($response::HTTP_OK);

        $response->setContent("{\"title\": \"Borjita\", \"type\": \"oEt Calmes!\"}");

        return $response;
    }
}