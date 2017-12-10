<?php

namespace APIRoche;

use Memcached;
use GuzzleHttp\Client;

class BotRequest {

    const NEXT_ID_KEY = "NEXT_ID_KEY";
    const DIALOG_FLOW_API_KEY = "Bearer 92dd6a6b0c2049ce8d8fc5d8b47c9752";
    const DIALOG_FLOW_BASE_URL = "https://api.dialogflow.com/v1/query?v=20150910";

    const NOT_UNDERSTANDING_RESPONSES =  array(
        'Sorry, I did\'t understand it.',
        'Can you repeat it please.',
        'I did not find anything about that topic.'
    );

    private $memcached;

    public function __construct() {

        $this->memcached = new Memcached();
        $this->memcached ->addServer('localhost', 11211);
    }

    public function request (String $inputText, int $sessionID) {

        $client = new Client();

        $requestContent = array();
        $headers = array();
        $params = array();

        $headers['Authorization'] =  BotRequest::DIALOG_FLOW_API_KEY;
        $headers['Content-Type'] =  'application/json';

        $params['lang'] = 'en';
        $params['query'] = $inputText;
        $params['sessionId'] = $sessionID;

        $requestContent['headers'] = $headers;
        $requestContent['body'] = json_encode($params);

        $response = $client->post(BotRequest::DIALOG_FLOW_BASE_URL, $requestContent);

        return $this->handleBotResponse(json_decode($response->getBody()));
        //return $response->getBody();
    }

    public function getSessionID(): int {

        if ($this->memcached->get(BotRequest::NEXT_ID_KEY) == FALSE) $this->memcached->set(BotRequest::NEXT_ID_KEY, 0);
        $id = $this->memcached->get(BotRequest::NEXT_ID_KEY);

        $this->memcached->set(BotRequest::NEXT_ID_KEY, $id + 1);

        return $id;
    }

    private function handleBotResponse ($botResponse) {

        $response = array();

        if ($botResponse->result->fulfillment->speech == '') $response = $this->notUnderstandResponse($response);
        else {
            switch ($botResponse->result->metadata->intentName) {

                case 'example_questions':
                    $response = $this->exampleQuestions($botResponse);
                    break;

                case 'disease_status_period_country':
                    $response = $this->diseaseStatusPeriodCountry($botResponse, $response);
                    break;

            }
        }

        return json_encode($response);
    }

    private function notUnderstandResponse ($response) {

        $response['bot_answer'] = BotRequest::NOT_UNDERSTANDING_RESPONSES[rand(0, count(BotRequest::NOT_UNDERSTANDING_RESPONSES) - 1)];
        $response['Response-Type'] = 'plain';

        return $response;
    }

    private function exampleQuestions($botResponse) {

        $response['bot_answer'] = $botResponse->result->fulfillment->messages[0]->speech;
        $response['Response-Type'] = 'list';
        $response['data_source'] = $botResponse->result->fulfillment->messages[1]->payload->data_source;

        return $response;
    }

    private function diseaseStatusPeriodCountry($botResponse, $response) {

        $response['bot_answer'] = $botResponse->result->fulfillment->messages[0]->speech;
        $response['Response-Type'] = 'chart';
        $response['chart_type'] = array('line');
        $response['data_source'] = DAOStudy::getInstance()->diseaseStatusPeriodCountry($botResponse->result->parameters->{'geo-country'}, $botResponse->result->parameters->Disease);

        return $response;
    }
}