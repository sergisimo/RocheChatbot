<?php

namespace APIRoche;

use Memcached;
use GuzzleHttp\Client;

class BotRequest {

    const NEXT_ID_KEY = "NEXT_ID_KEY";
    const DIALOG_FLOW_API_KEY = "Bearer 92dd6a6b0c2049ce8d8fc5d8b47c9752";
    const DIALOG_FLOW_BASE_URL = "https://api.dialogflow.com/v1/query?v=20150910";

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
    }

    public function getSessionID(): int {

        if ($this->memcached->get(BotRequest::NEXT_ID_KEY) == FALSE) $this->memcached->set(BotRequest::NEXT_ID_KEY, 0);
        $id = $this->memcached->get(BotRequest::NEXT_ID_KEY);

        $this->memcached->set(BotRequest::NEXT_ID_KEY, $id + 1);

        return $id;
    }

    private function handleBotResponse ($botResponse) {

        $response = array();

        switch ($botResponse->result->metadata->intentName) {

            case 'example_questions':
                $response['bot_answer'] = $botResponse->result->fulfillment->messages[0]->speech;
                $response['Response-Type'] = 'list';
                $response['data_source'] = $botResponse->result->fulfillment->messages[1]->payload->data_source;
                break;

        }

        return json_encode($response);
    }
}