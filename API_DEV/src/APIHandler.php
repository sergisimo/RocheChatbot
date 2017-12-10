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

            if ($request->request->has('keywords')) $keywords = $request->request->get('keywords');
            else $keywords = null;
            if ($request->request->has('study_types')) $studyType = $request->request->get('study_types');
            else $studyType = null;
            if ($request->request->has('study_results')) $studyResults = $request->request->get('study_results');
            else $studyResults = null;
            if ($request->request->has('study_status')) $studyStatus = $request->request->get('study_status');
            else $studyStatus = null;
            if ($request->request->has('sex')) $sex = $request->request->get('sex');
            else $sex = null;
            if ($request->request->has('countries')) $countries = $request->request->get('countries');
            else $countries = null;
            if ($request->request->has('location_terms')) $cities = $request->request->get('location_terms');
            else $cities = null;
            if ($request->request->has('conditions')) $conditions = $request->request->get('conditions');
            else $conditions = null;
            if ($request->request->has('phases')) $phases = $request->request->get('phases');
            else $phases = null;

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