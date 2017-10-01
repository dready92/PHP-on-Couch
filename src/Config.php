<?php

namespace PHPOnCouch;


use Dotenv\Dotenv;
use Dotenv\Exception\InvalidPathException;

class Config
{

    private static $instance = null;
    private static $adapterKey = 'HTTP_ADAPTER';
    private $config;

    private function __construct()
    {
        $env = new Dotenv(__DIR__);
        try {
            $env->load();
        } catch (InvalidPathException $e) {
            //The file is not available :(
        }
        //Default
        if (empty(getenv(self::$adapterKey)))
            putenv(self::$adapterKey . "=curl");

        $env->required(self::$adapterKey)->allowedValues(['curl', 'socket']);

        //Get curl options
        $curlOpts = [];
        foreach ($_SERVER as $key => $val) {
            if (substr($key, 0, 7) == 'CURLOPT')
                $curlOpts[$key] = $val;
        }

        $this->config = [
            self::$adapterKey => getenv(self::$adapterKey),
            'curl' => $curlOpts
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
        if (self::$instance == null)
            self::$instance = new Config();
        return self::$instance;
    }
}

