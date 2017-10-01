<?php

/*
 * Copyright (C) 2016 Alexis
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace PHPOnCouch\Exceptions;

use Exception;
use InvalidArgumentException;

/**
 * customized Exception class for CouchDB errors
 *
 * this class uses : the Exception message to store the HTTP message sent by the server
 * the Exception code to store the HTTP status code sent by the server
 * and adds a method getBody() to fetch the body sent by the server (if any)
 *
 */
class CouchException extends Exception
{

    // CouchDB response codes we handle specialized exceptions
    protected static $codeSubtypes = [
        401 => 'CouchUnauthorizedException',
        403 => 'CouchForbiddenException',
        404 => 'CouchNotFoundException',
        417 => 'CouchExpectationException'
    ];
    // more precise response problem
    protected static $statusSubtypes = ['Conflict' => 'CouchConflictException'];
    // couchDB response once parsed
    protected $couchResponse = [];

    /**
     * class constructor
     *
     * @param string|array $response HTTP response from the CouchDB server
     * @param string $method the HTTP method
     * @param string $url the target URL
     * @param mixed $parameters the query parameters
     */
    function __construct($response, $method = null, $url = null, $parameters = null)
    {
        $this->couchResponse = is_string($response) ? self::parseRawResponse($response) : $response;
        if (is_object($this->couchResponse['body']) && isset($this->couchResponse['body']->reason))
            $message = $this->couchResponse['status_message'] . ' - ' . $this->couchResponse['body']->reason;
        else
            $message = $this->couchResponse['status_message'];
        if ($method)
            $message .= " ($method $url " . json_encode($parameters) . ')';
        $statusCode = isset($this->couchResponse['status_code']) ? $this->couchResponse['status_code'] : null;
        if (empty($statusCode))
            parent::__construct($message);
        else
            parent::__construct($message, $statusCode);
    }

    /**
     * Factory that returns the appropriate exception based on the error code.
     * @param string|array $response The raw response
     * @param string $method The method
     * @param string $url The URL
     * @param mixed $parameters The parameters
     * @return Exception|CouchException Returns the appropriate exception.
     */
    public static function factory($response, $method, $url, $parameters)
    {
        if (is_string($response))
            $response = self::parseRawResponse($response);
        if (!$response)
            return new CouchNoResponseException();
        if (isset($response['status_code']) && isset(self::$codeSubtypes[$response['status_code']])) {
            $class = __NAMESPACE__ . '\\' . self::$codeSubtypes[$response['status_code']];
            return new $class($response, $method, $url, $parameters);
        } elseif (isset($response['status_message']) && isset(self::$statusSubtypes[$response['status_message']])) {
            $class = __NAMESPACE__ . '\\' . self::$statusSubtypes[$response['status_message']];
            return new $class($response, $method, $url, $parameters);
        } else {
            return new self($response, $method, $url, $parameters);
        }
    }

    /**
     * returns CouchDB server response body (if any)
     *
     * if the response's "Content-Type" is set to "application/json", the
     * body is json_decode()d
     *
     * @return string|object|null CouchDB server response
     */
    public function getBody()
    {
        if (isset($this->couchResponse['body']))
            return $this->couchResponse['body'];
        return null;
    }

    /**
     * parse a CouchDB server response and sends back an array
     * the array contains keys :
     * status_code : the HTTP status code returned by the server
     * status_message : the HTTP message related to the status code
     * body : the response body (if any). If CouchDB server response Content-Type is application/json
     *        the body will by json_decode()d
     *
     * @static
     * @param string $rawData data sent back by the server
     * @param boolean $jsonAsArray is true, the json response will be decoded as an array.
     *  If false, it's decoded as an object
     * @return array CouchDB response
     * @throws InvalidArgumentException
     */
    public static function parseRawResponse($rawData, $jsonAsArray = false)
    {
        if (!strlen($rawData))
            throw new InvalidArgumentException("no data to parse");
        while (!substr_compare($rawData, "HTTP/1.1 100 Continue\r\n\r\n", 0, 25)) {
            $rawData = substr($rawData, 25);
        }
        $response = ['body' => null];
        list($headers, $body) = explode("\r\n\r\n", $rawData, 2);
        $headersArray = explode("\n", $headers);
        $statusLine = reset($headersArray);
        $statusArray = explode(' ', $statusLine, 3);
        $response['status_code'] = trim($statusArray[1]);
        $response['status_message'] = trim($statusArray[2]);
        if (strlen($body)) {
            if (preg_match('@Content-Type:\s+application/json@i', $headers))
                $response['body'] = json_decode($body, $jsonAsArray);
            else
                $response['body'] = $body;
        }
        return $response;
    }

}






