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

    function str_replace_first($from, $to, $content)
    {
        $from = '/'.preg_quote($from, '/').'/';

        return preg_replace($from, $to, $content, 1);
    }


    protected function validatePhoneNumber($phone_number)
    {
        array_push($this->receipients, $phone_number);

        return true;
    }

    protected function startsWith($haystack, $needle)
    {
        $length = strlen($needle);

        return (substr($haystack, 0, $length) === $needle);
    }

    private function formatPhoneNumbers()
    {
        return collect($this->receipients)->map(function($phone_number) {
            $phone_number = str_replace('+', '', $phone_number);
            $phone_number = str_replace(' ', '', $phone_number);
            
            $pos = strpos($phone_number, '0');

            if ($pos !== false) {
                return substr_replace($phone_number, '254', 0, strlen('0'));
            }

            return $phone_number;
        });
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
                'receipients' => $this->formatPhoneNumbers()
            ]
        ]);

        return [
            'status' => $response->getStatusCode(),
            'message' => $response->getBody()->getContents()
        ];
    }
}