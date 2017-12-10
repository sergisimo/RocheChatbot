<?php

namespace APIRoche;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

class APIHandler {

    public function getBotResponse(Request $request) {

        $bot = new BotRequest();
        $response = new JsonResponse();

        if ($request->request->has('botText') && $request->request->has('sessionID')) {

            $response->setContent($bot->request($request->request->get('botText'), $request->request->get('sessionID')));
            $response->setStatusCode($response::HTTP_OK);
        } else {

            $response->setContent($this->generateBadRequestError());
            $response->setStatusCode($response::HTTP_BAD_REQUEST);
        }

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

    public function getFormResults(Request $request) {

        $response = new JsonResponse();

        if (true) {

            $keywords = $request->request->get('keywords');
            $studyType = $request->request->get('study_types');
            $studyResults = $request->request->get('study_results');
            $studyStatus = $request->request->get('study_status');
            $sex = $request->request->get('sex');
            $countries = $request->request->get('countries');
            $cities = $request->request->get('location_terms');
            $conditions = $request->request->get('conditions');
            $phases = $request->request->get('phases');

            $response->setContent(json_encode(DAOStudy::getInstance()->formSearch($keywords, $studyType, $studyResults, $studyStatus, $sex, $countries, $cities, $conditions, $phases)));
            $response->setStatusCode($response::HTTP_OK);

        } else {

            $response->setContent($this->generateBadRequestError());
            $response->setStatusCode($response::HTTP_BAD_REQUEST);
        }

        return $response;
    }

    private function generateBadRequestError() {

        $errorMessage = array();
        $error = array();

        $error['title'] = 'Bad Request';
        $error['message'] = 'Bad request, malformed syntax or missing parameters.';

        $errorMessage['error'] = $error;

        return json_encode($errorMessage);
    }
}