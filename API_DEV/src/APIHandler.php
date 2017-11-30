<?php

namespace APIRoche;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

class APIHandler {

    public function indexAction() {

        $response = new JsonResponse();

        $response->setStatusCode($response::HTTP_OK);

        $response->setContent("{\"title\": \"Borjita\", \"type\": \"oEt Calmes!\"}");

        return $response;
    }

    public function getBotResponse(Request $request) {

        $bot = new BotRequest();
        $response = new JsonResponse();

        $response->setContent($bot->request($request->request->get('botText'), $request->request->get('sessionID')));
        $response->setStatusCode($response::HTTP_OK);

        return $response;
    }

    public function getIdAction() {

        $bot = new BotRequest();
        $response = new JsonResponse();

        $responseContent = array();
        $responseContent['sessionID'] = $bot->getSessionID();

        $response->setStatusCode($response::HTTP_OK);
        $response->setContent(json_encode($responseContent));

        return $response;
    }
}