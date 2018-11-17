<?php

/*
  Copyright (C) 2009  Mickael Bailly

  This program is free software: you can redistribute it and/or modify
  it under the terms of the GNU Lesser General Public License as published by
  the Free Software Foundation, either version 3 of the License, or
  (at your option) any later version.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU Lesser General Public License for more details.

  You should have received a copy of the GNU Lesser General Public License
  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace PHPOnCouch;

use Exception;
use InvalidArgumentException;
use PHPOnCouch\Exceptions\CouchException;
use PHPOnCouch\Exceptions\ConflictException;
use PHPOnCouch\Exceptions\CouchNoResponseException;
use PHPOnCouch\Exceptions\ForbiddenException;
use PHPOnCouch\Exceptions\NotFoundException;
use PHPOnCouch\Exceptions\UnauthorizedException;
use PHPOnCouch\Exceptions\ExpectationException;
use PHPOnCouch\Adapter\CouchHttpAdapterInterface;
use PHPOnCouch\Adapter\CouchHttpAdapterCurl;
use PHPOnCouch\Adapter\CouchHttpAdapterSocket;


/**
 * couch class
 *
 * basics to implement JSON / REST / HTTP CouchDB protocol
 *
 */
class Couch
{

    /**
     * @var string database source name
     */
    protected $dsn = '';

    /**
     * @var array database source name parsed
     */
    protected $dsnParsed = null;

    /**
     * @var array couch options
     */
    protected $options = null;

    /**
     * class constructor
     *
     * @param string $dsn CouchDB Data Source Name
     * @param array $options Couch options
     */
    public function __construct($dsn, $options = [])
    {
        $this->dsn = preg_replace('@/+$@', '', $dsn);
        $this->options = $options;
        $this->dsnParsed = parse_url($this->dsn);
        if (!isset($this->dsnParsed['port'])) {
            $this->dsnParsed['port'] = 80;
        }
    }

    /**
     * Returns and init if necessary, the CouchHttpAdapter
     * @returns \PHPOnCouch\Adapter\CouchHttpAdapterInterface
     */
    public function getAdapter()
    {
        if (!isset($this->adapter)) {
            $this->adapter = $this->initAdapter($this->options);
        }
        return $this->adapter;
    }

    /**
     * Set a new adapter to the Couch Class.
     * @param \PHPOnCouch\Adapter\CouchHttpAdapterInterface $adapter The adapter to set
     */
    public function setAdapter(CouchHttpAdapterInterface $adapter)
    {
        $this->adapter = $adapter;
    }

    /**
     * Init the HTTP Adapter with cURL if available.
     * @param array $options An array of options.
     * @return \PHPOnCouch\Adapter\CouchHttpAdapterSocket
     */
    public function initAdapter($options)
    {
        if (!isset($options))
            $options = $this->options;

        $config = Config::getInstance();

        if ($config->getAdapter() == 'curl' && function_exists('curl_init')) {
            //We add curl options from config
            if (!array_key_exists('curl', $options) || !is_array($options['curl']))
                $options['curl'] = [];
            $options['curl'] = array_merge($config->getCurlOpts(), $options['curl']);

            //Convert options to int
            $newOpts = [];
            foreach ($options['curl'] as $key => $val) {
                if (is_string($key))
                    $key = constant($key);
                $newOpts[$key] = $val;
            }
            $options['curl'] = $newOpts;
            $adapter = new CouchHttpAdapterCurl($this->dsn, $options);
        } else
            $adapter = new CouchHttpAdapterSocket($this->dsn, $options);
        $this->adapter = $adapter;
        return $adapter;
    }

    /**
     * returns the DSN, untouched
     *
     * @return string DSN
     */
    public function dsn()
    {
        return $this->dsn;
    }

    /**
     * returns the options array
     *
     * @return array containing the option
     */
    public function options()
    {
        return $this->options;
    }

    /**
     * set the session cookie to send in the headers
     * @param string $cookie the session cookie
     * ( example : AuthSession=Y291Y2g6NENGNDgzNz )
     *
     * @return \PHPOnCouch\Couch
     */
    public function setSessionCookie($cookie)
    {
        return $this->getAdapter()->setSessionCookie($cookie);
    }

    /**
     * Get the current session cookie set to the class.
     * @return String The cookie
     */
    public function getSessionCookie()
    {
        return $this->getAdapter()->getSessionCookie();
    }

    /**
     * return a part of the data source name
     *
     * if $part parameter is empty, returns dns array
     *
     * @param string $part part to return
     * @return string|array DSN part
     */
    public function dsnPart($part = null)
    {
        if (!$part) {
            return $this->dsnParsed;
        }
        if (isset($this->dsnParsed[$part])) {
            return $this->dsnParsed[$part];
        }
        return $this->dsnParsed;
    }

    /**
     * parse a CouchDB server response and sends back an array
     * the array contains keys :
     * status_code : the HTTP status code returned by the server
     * status_message : the HTTP message related to the status code
     * body : the response body (if any). If CouchDB server response
     * Content-Type is application/json
     *        the body will by json_decode()d
     *
     * @static
     * @param string|boolean $rawData data sent back by the server
     * @param boolean $jsonAsArray is true, the json response will be
     * decoded as an array. Is false, it's decoded as an object
     * @return array CouchDB response
     * @throws InvalidArgumentException
     */
    public static function parseRawResponse($rawData, $jsonAsArray = false)
    {
        if (!strlen($rawData))
            throw new InvalidArgumentException("no data to parse");
        $httpHeader = "HTTP/1.1 100 Continue\r\n\r\n";
        while (!substr_compare($rawData, $httpHeader, 0, 25)) {
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
            $regex = '@Content-Type:\s+application/json@i';
            if (preg_match($regex, $headers))
                $response['body'] = json_decode($body, $jsonAsArray);
            else
                $response['body'] = $body;
        }
        return $response;
    }

    /**
     * send a query to the CouchDB server
     *
     * @param string $method HTTP method to use (GET, POST, ...)
     * @param string $url URL to fetch
     * @param array $parameters additionnal parameters to send with the request
     * @param string|array|object $data request body
     * @param string $contentType the content type of the sent data
     *  (defaults to application/json)
     *
     * @return string|false server response on success, false on error
     * @throws Exception
     */
    public function query($method, $url, $parameters = [], $data = null, $contentType = null)
    {
        return $this->getAdapter()->query($method, $url, $parameters, $data, $contentType);
    }

    /**
     * record a file located on the disk as a CouchDB attachment
     *
     * @param string $url CouchDB URL to store the file to
     * @param string $file path to the on-disk file
     * @param string $contentType attachment content_type
     *
     * @return string server response
     */
    public function storeFile($url, $file, $contentType)
    {
        return $this->getAdapter()->storeFile($url, $file, $contentType);
    }

    /**
     * store some data as a CouchDB attachment
     *
     * @param string $url CouchDB URL to store the file to
     * @param string $data data to send as the attachment content
     * @param string $contentType attachment content_type
     *
     * @return string server response
     */
    public function storeAsFile($url, $data, $contentType)
    {
        return $this->getAdapter()->storeAsFile($url, $data, $contentType);
    }

    /**
     * send a query to the CouchDB server
     *
     * In a continuous query, the server send headers, and then a JSON object per line.
     * On each line received, the $callable callback is fired, with two arguments :
     *
     * - the JSON object decoded as a PHP object
     *
     * - a couchClient instance to use to make queries inside the callback
     *
     * If the callable returns the boolean false , continuous reading stops.
     *
     * @param callable $callable PHP function name / callable array ( see http://php.net/is_callable )
     * @param string $method HTTP method to use (GET, POST, ...)
     * @param string $url URL to fetch
     * @param array $parameters additional parameters to send with the request
     * @param string|array|object $data request body
     *
     * @param null $caller The caller object that will be passed to the continuousQuery callback. By default, it's the Couch instance.
     * @return string|false server response on success, false on error
     *
     * @throws CouchException
     */
    public function continuousQuery($callable, $method, $url, $parameters = [], $data = null, $caller = null)
    {
        $callerVal = empty($caller) ? $this : $caller;
        return $this->getAdapter()->continuousQuery($callable, $method, $url, $parameters, $data, $callerVal);
    }

}
