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
            throw new Exception('Invalid receipients format');
        }

        return $this;
    }

    protected function validatePhoneNumber($phone_number)
    {
        if($this->startsWith($phone_number, '0')) {
            if(strlen($phone_number) == 10) {
                array_push($this->receipients, $phone_number);

                return true;
            }

            throw new InvalidPhoneNumberException($phone_number . ' is an invalid phone number');
        }
        else if($this->startsWith($phone_number, '254')) {
            if(strlen($phone_number) == 12) {
                array_push($this->receipients, $phone_number);

                return true;
            }

            throw new InvalidPhoneNumberException($phone_number . ' is an invalid phone number');
        }
        elseif(strlen((string)$phone_number) == 11) {
            array_push($this->receipients, (string)$phone_number);

            return true;
        }
        elseif(strlen((string)$phone_number) == 9) {
            array_push($this->receipients, '0' . (string)$phone_number);

            return true;
        }

        throw new InvalidPhoneNumberException($phone_number . ' is an invalid phone number');
    }

    protected function startsWith($haystack, $needle)
    {
        $length = strlen($needle);

        return (substr($haystack, 0, $length) === $needle);
    }

    public function send()
    {
        $client = new Client([
            'base_uri' => $this->server_api
        ]);
        
        $response = $client->request('POST', '/api/v1/gateway/sms', [
            'json' => [
                'message' => $this->message,
                'app_key' => $this->app_key,
                'app_secret' => $this->app_secret,
                'receipients' => $this->receipients
            ]
        ]);

        return [
            'status' => $response->getStatusCode(),
            'message' => $response->getBody()->getContents()
        ];
    }
}