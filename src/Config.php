<?php

namespace PHPOnCouch;

class Config
{

    private static $instance = null;
    private static $adapterKey = 'HTTP_ADAPTER';
    private $config;

    private function __construct()
    {
        //Default
        if (empty(getenv(self::$adapterKey))) {
            putenv(self::$adapterKey."=curl");
        }

        $adapterConfigValue = getenv(self::$adapterKey);
        $allowedValues = ['curl', 'socket'];
        if (!in_array($adapterConfigValue, ['curl', 'socket'])) {
            throw new \Exception(
                "Invalid adapter configuration provided: ".self::$adapterKey."=$adapterConfigValue."." AllowedValues: ".implode(
                    ',',
                    $allowedValues
                )
            );
        }

        //Get curl options
        $curlOpts = [];
        foreach ($_SERVER as $key => $val) {
            if (substr($key, 0, 7) == 'CURLOPT') {
                $curlOpts[$key] = $val;
            }
        }

        $this->config = [
            self::$adapterKey =>$adapterConfigValue,
            'curl' => $curlOpts,
        ];
    }

    public function getAdapter()
    {
        return $this->config[self::$adapterKey];
    }


    public function getCurlOpts()
    {
        return $this->config['curl'];
    }


    public static function getInstance()
    {
        if (self::$instance == null) {
            self::$instance = new Config();
        }

        return self::$instance;
    }
}

