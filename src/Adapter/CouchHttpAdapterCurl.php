<?php

/*
  Copyright (C) 2017  Alexis Cote

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

namespace PHPOnCouch\Adapter;

use Exception;
use InvalidArgumentException;


/**
 * Description of CouchAdapterCurl
 *
 * @author alexis
 */
class CouchHttpAdapterCurl extends AbstractCouchHttpAdapter implements CouchHttpAdapterInterface
{


    /**
     * We need a socket to use the continuous query.
     * @var CouchHttpAdapterSocket
     */
    protected $socketAdapter;


    protected function initSocketAdapter()
    {
        $this->socketAdapter = new CouchHttpAdapterSocket($this->getDsn(), $this->getOptions());
        if ($this->hasSessionCookie()) {
            $this->socketAdapter->setSessionCookie($this->getSessionCookie());
        }
    }

    public function continuousQuery($callable, $method, $url, $parameters = [], $data = null, $caller = null)
    {
        if ($this->socketAdapter == null) {
            $this->initSocketAdapter();
        }
        return $this->socketAdapter->continuousQuery($callable, $method, $url, $parameters, $data, $caller);
    }

    /**
     * add user-defined options to Curl resource
     */
    protected function addCustomOptions($res)
    {
        if (array_key_exists("curl", $this->options) && is_array($this->options["curl"])) {
            curl_setopt_array($res, $this->options["curl"]);
        }
    }

    /**
     * build HTTP request to send to the server
     * uses PHP cURL API
     *
     * @param string $method HTTP method to use
     * @param string $url the request URL
     * @param string|object|array $data the request body. If it's an array or an object, $data is json_encode()d
     * @param string $contentType the content type of the sent data (defaults to application/json)
     * @return resource CURL request resource
     */
    protected function buildRequest($method, $url, $data, $contentType)
    {
        $http = curl_init($url);
        $httpHeaders = ['Accept: application/json,text/html,text/plain,*/*'];
        if (is_object($data) || is_array($data)) {
            $data = json_encode($data);
        }
        if ($contentType) {
            $httpHeaders[] = 'Content-Type: ' . $contentType;
        } else {
            $httpHeaders[] = 'Content-Type: application/json';
        }
        if ($this->sessioncookie) {
            $httpHeaders[] = "Cookie: " . $this->sessioncookie;
        }
        curl_setopt($http, CURLOPT_CUSTOMREQUEST, $method);

        if ($method == 'COPY') {
            $httpHeaders[] = "Destination: $data";
        } elseif ($data) {
            curl_setopt($http, CURLOPT_POSTFIELDS, $data);
        }
        $httpHeaders[] = 'Expect: ';
        curl_setopt($http, CURLOPT_HTTPHEADER, $httpHeaders);
        return $http;
    }

    /**
     * send a query to the CouchDB server
     * uses PHP cURL API
     *
     * @param string $method HTTP method to use (GET, POST, ...)
     * @param string $url URL to fetch
     * @param array $parameters additionnal parameters to send with the request
     * @param string|array|object $data request body
     * @param string $contentType the content type of the sent data (defaults to application/json)
     *
     * @return string|false server response on success, false on error
     *
     * @throws Exception
     */
    public function query($method, $url, $parameters = [], $data = null, $contentType = null)
    {
        if (!in_array($method, $this->httpMethods))
            throw new Exception("Bad HTTP method: $method");

        $url = $this->dsn . $url;
        if (is_array($parameters) && count($parameters))
            $url = $url . '?' . http_build_query($parameters);
        $http = $this->buildRequest($method, $url, $data, $contentType);
        $this->addCustomOptions($http);
        curl_setopt($http, CURLOPT_HEADER, true);
        curl_setopt($http, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($http, CURLOPT_FOLLOWLOCATION, true);

        $response = curl_exec($http);
        curl_close($http);
        return $response;
    }

    /**
     * record a file located on the disk as a CouchDB attachment
     * uses PHP cURL API
     *
     * @param string $url CouchDB URL to store the file to
     * @param string $file path to the on-disk file
     * @param string $contentType attachment content_type
     *
     * @return string server response
     *
     * @throws InvalidArgumentException
     */
    public function storeFile($url, $file, $contentType)
    {
        if (!strlen($url))
            throw new InvalidArgumentException("Attachment URL can't be empty");
        if (!strlen($file) || !is_file($file) || !is_readable($file))
            throw new InvalidArgumentException("Attachment file does not exist or is not readable");
        if (!strlen($contentType))
            throw new InvalidArgumentException("Attachment Content Type can't be empty");
        $url = $this->dsn . $url;
        $http = curl_init($url);
        $httpHeaders = [
            'Accept: application/json,text/html,text/plain,*/*',
            'Content-Type: ' . $contentType,
            'Expect: '
        ];
        if ($this->sessioncookie) {
            $httpHeaders[] = "Cookie: " . $this->sessioncookie;
        }
        curl_setopt($http, CURLOPT_PUT, 1);
        curl_setopt($http, CURLOPT_HTTPHEADER, $httpHeaders);
        curl_setopt($http, CURLOPT_UPLOAD, true);
        curl_setopt($http, CURLOPT_HEADER, true);
        curl_setopt($http, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($http, CURLOPT_FOLLOWLOCATION, true);
        $fstream = fopen($file, 'r');
        curl_setopt($http, CURLOPT_INFILE, $fstream);
        curl_setopt($http, CURLOPT_INFILESIZE, filesize($file));
        $this->addCustomOptions($http);
        $response = curl_exec($http);
        fclose($fstream);
        curl_close($http);
        return $response;
    }

    /**
     * store some data as a CouchDB attachment
     * uses PHP cURL API
     *
     * @param string $url CouchDB URL to store the file to
     * @param string $data data to send as the attachment content
     * @param string $contentType attachment content_type
     *
     * @return string server response
     *
     * @throws InvalidArgumentException
     */
    public function storeAsFile($url, $data, $contentType)
    {
        if (!strlen($url))
            throw new InvalidArgumentException("Attachment URL can't be empty");
        if (!strlen($contentType))
            throw new InvalidArgumentException("Attachment Content Type can't be empty");
        $url = $this->dsn . $url;
        $http = curl_init($url);
        $httpHeaders = [
            'Accept: application/json,text/html,text/plain,*/*',
            'Content-Type: ' . $contentType,
            'Expect: ',
            'Content-Length: ' . strlen($data)
        ];
        if ($this->sessioncookie) {
            $httpHeaders[] = "Cookie: " . $this->sessioncookie;
        }
        curl_setopt($http, CURLOPT_CUSTOMREQUEST, 'PUT');
        curl_setopt($http, CURLOPT_HTTPHEADER, $httpHeaders);
        curl_setopt($http, CURLOPT_HEADER, true);
        curl_setopt($http, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($http, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($http, CURLOPT_POSTFIELDS, $data);
        $this->addCustomOptions($http);
        $response = curl_exec($http);
        curl_close($http);
        return $response;
    }

    public function setSessionCookie($cookie)
    {
        if ($this->socketAdapter) {
            $this->socketAdapter->setSessionCookie($cookie);
        }
        return parent::setSessionCookie($cookie);
    }

    public function setOptions($options)
    {
        if ($this->socketAdapter) {
            $this->socketAdapter->setOptions($options);
        }
        parent::setOptions($options);
    }
}
