<?php

namespace FutureFast\Tradesk;

use FutureFast\Tradesk\Exceptions\InvalidConfigException;
use FutureFast\Tradesk\Exceptions\InvalidPhoneNumberException;
use GuzzleHttp\Client;

class SMS
{
    public $app_key, $app_secret, $message;
    public $server_api = 'http://api.tradesk.co.ke';
    public $receipients = [];

    function __construct($app_key, $app_secret)
    {
        $this->app_key = $app_key;
        $this->app_secret = $app_secret;
    }

    public static function config(Array $settings)
    {
        if(! isset($settings['app_key']) || ! isset($settings['app_secret'])) {
            throw new InvalidConfigException('Invalid configuration. Please check that app_key and app_secret are defined');
        }

        return new self($settings['app_key'], $settings['app_secret']);
    }

    public function message($message)
    {
        $this->message = $message;

        return $this;
    }

    public function to($receipients)
    {
        $type = gettype($receipients);

        if($type == 'string' || $type == 'integer') {
            $this->validatePhoneNumber($receipients);
        }
        else if ($type == 'array') {
            foreach($receipients as $receipient) {
                $this->validatePhoneNumber($receipient);
            }
        }
        else {
            throw new \Exception('Invalid receipients format');
        }

        return $this;
    }

    protected function validatePhoneNumber($phone_number)
    {
        array_push($this->receipients, '254' . substr($phone_number, -9));

        return true;
    }

    public function send()
    {
        $client = new Client([
            'base_uri' => $this->server_api
        ]);
        
        $response = $client->request('POST', '/api/v1/gateway/sms', [
            'json' => [
                'app_key' => $this->app_key,
                'app_secret' => $this->app_secret,
                'message' => $this->message,
                'receipients' => $this->receipients
            ]
        ]);

        return [
            'status' => $response->getStatusCode(),
            'message' => $response->getBody()->getContents()
        ];
    }
}