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

use InvalidArgumentException;
use PHPOnCouch\Exceptions\CouchException;
use Exception;

/**
 * Description of CouchAdapterSocket
 *
 * @author alexis
 */
class CouchHttpAdapterSocket extends AbstractCouchHttpAdapter implements CouchHttpAdapterInterface
{

    protected $socket;

    /**
     * open the connection to the CouchDB server
     * @return bool Weither the connection is successful
     *
     * @throws Exception
     * @internal param bool $stream True to setup a stream client, otherwise false.
     *
     * This function can throw an Exception if it fails
     *
     */
    protected function connect()
    {
        $ssl = $this->dsnPart('scheme') == 'https' ? 'ssl://' : '';
        $errNum = -1;
        $errStr = '';
        $this->socket = fsockopen($ssl . $this->dsnPart('host'), $this->dsnPart('port'), $errNum, $errStr);
        if (!$this->socket) {
            $errMsg = 'Could not open connection to ' . $this->dsnPart('host') . ':';
            $errMsg .= $this->dsnPart('port') . ': ' . $errStr . ' (' . $errNum . ')';
            throw new Exception($errMsg);
        }
        return true;
    }

    /**
     * send the HTTP request to the server and read the response
     *
     * @param string $request HTTP request to send
     * @return string $response HTTP response from the CouchDB server
     */
    protected function execute($request)
    {
        fwrite($this->socket, $request);
        $response = '';
        while (!feof($this->socket))
            $response .= fgets($this->socket);
        return $response;
    }

    /**
     * Closes the connection to the server
     */
    protected function disconnect()
    {
        @fclose($this->socket);
        $this->socket = null;
    }

    /**
     * returns first lines of request headers
     *
     * lines :
     * <code>
     * VERB HTTP/1.0
     * Host: my.super.server.com
     * Authorization: Basic...
     * Accept: application/json,text/html,text/plain,* /*
     * </code>
     *
     * @param string $method HTTP method to use
     * @param string $url the request URL
     * @return string start of HTTP request
     */
    protected function startRequestHeaders($method, $url)
    {
        if ($this->dsnPart('path'))
            $url = $this->dsnPart('path') . $url;
        $req = "$method $url HTTP/1.0\r\nHost: " . $this->dsnPart('host') . "\r\n";
        if ($this->dsnPart('user') && $this->dsnPart('pass')) {
            $req .= 'Authorization: Basic ' . base64_encode($this->dsnPart('user') . ':' .
                    $this->dsnPart('pass')) . "\r\n";
        } elseif ($this->sessioncookie) {
            $req .= "Cookie: " . $this->sessioncookie . "\r\n";
        }
        $req .= "Accept: application/json,text/html,text/plain,*/*\r\n";

        return $req;
    }

    /**
     * build HTTP request to send to the server
     *
     * @param string $method HTTP method to use
     * @param string $url the request URL
     * @param string|object|array $data the request body. If it's an array || an object, $data is json_encode()
     * @param string $contentType the content type of the sent data (defaults to application/json)
     * @return string HTTP request
     */
    protected function buildRequest($method, $url, $data, $contentType = null)
    {
        if (is_object($data) || is_array($data))
            $data = json_encode($data);
        $req = $this->startRequestHeaders($method, $url);
        if ($contentType) {
            $req .= 'Content-Type: ' . $contentType . "\r\n";
        } else {
            $req .= 'Content-Type: application/json' . "\r\n";
        }
        if ($method == 'COPY') {
            $req .= 'Destination: ' . $data . "\r\n\r\n";
        } elseif ($data) {
            $req .= 'Content-Length: ' . strlen($data) . "\r\n\r\n";
            $req .= $data . "\r\n";
        } else {
            $req .= "\r\n";
        }
        return $req;
    }

    /**
     * send a query to the CouchDB server
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

        if (is_array($parameters) && count($parameters))
            $url = $url . '?' . http_build_query($parameters);

        $request = $this->buildRequest($method, $url, $data, $contentType);
        if (!$this->connect())
            return false;
        $rawResponse = $this->execute($request);
        $this->disconnect();

        return $rawResponse;
    }

    /**
     * send a query to the CouchDB server
     *
     * In a continuous query, the server send headers, and then a JSON object per line.
     * On each line received, the $callable callback is fired, with two arguments :
     *
     * - the JSON object decoded as a PHP object
     *
     * - A caller object. If you execute this command from CouchClient, it will pass this instance in the callback.
     *
     * If the callable returns the boolean false , continuous reading stops.
     *
     * @param callable $callable PHP function name / callable array ( see http://php.net/is_callable )
     * @param string $method HTTP method to use (GET, POST, ...)
     * @param string $url URL to fetch
     * @param array $parameters additional parameters to send with the request
     * @param string|array|object $data request body
     *
     * @param null $caller The caller object passed to the callback function.
     * Default to null due to backward compatibility issue
     *
     * @return string|false server response on success, false on error
     * @throws CouchException
     */
    public function continuousQuery($callable, $method, $url, $parameters = [], $data = null, $caller = null)
    {
        if (!in_array($method, $this->httpMethods))
            throw new Exception("Bad HTTP method: $method");
        if (!is_callable($callable))
            throw new InvalidArgumentException("callable argument have to success to is_callable PHP function");
        $url = $this->dsn . $url;
        if (is_array($parameters) && count($parameters))
            $url = $url . '?' . http_build_query($parameters);
        //Send the request to the socket
        $request = $this->buildRequest($method, $url, $data, null);
        if (!$this->connect())
            return false;

        fwrite($this->socket, $request);
        //Read the headers and check that the response is valid
        $response = '';
        $hasHeaders = false;
        while (!feof($this->socket) && !$hasHeaders) {
            $response .= fgets($this->socket, 128);
            if ($response == "HTTP/1.1 100 Continue\r\n\r\n") {
                $response = '';
                continue;
            } //Ignore 'continue' headers, they will be followed by the real header.
            else if (preg_match("/\r\n\r\n$/", $response)) {
                $hasHeaders = true;
            }
        }
        $headers = explode("\n", trim($response));
        $split = explode(" ", trim(reset($headers)));
        $code = $split[1];
        unset($split);
        //If an invalid response is sent, read the rest of the response and throw an appropriate CouchException
        if (!in_array($code, [200, 201])) {
            stream_set_blocking($this->socket, false);
            $response .= stream_get_contents($this->socket);
            fclose($this->socket);
            throw CouchException::factory($response, $method, $url, $parameters);
        }

        //For as long as the socket is open, read lines and pass them to the callback
        $clone = $caller ? clone $caller : null;
        while ($this->socket && !feof($this->socket)) {
            $e = null;
            $e2 = null;
            $read = [$this->socket];
            ($numChangedStreams = stream_select($read, $e, $e2, 1));
            if (false === $numChangedStreams) {
                $this->disconnect();
            } elseif ($numChangedStreams > 0) {
                $line = fgets($this->socket);
                if (strlen(trim($line))) {
                    $break = call_user_func($callable, json_decode($line), $clone);
                    if ($break === false)
                        $this->disconnect();
                }
            }
        }
        return $code;
    }

    /**
     * record a file located on the disk as a CouchDB attachment
     * uses PHP socket API
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
        $req = $this->startRequestHeaders('PUT', $url);
        $req .= 'Content-Length: ' . filesize($file) . "\r\n"
            . 'Content-Type: ' . $contentType . "\r\n\r\n";
        $fstream = fopen($file, 'r');
        $this->connect();
        fwrite($this->socket, $req);
        stream_copy_to_stream($fstream, $this->socket);
        $response = '';
        while (!feof($this->socket))
            $response .= fgets($this->socket);
        $this->disconnect();
        fclose($fstream);
        return $response;
    }

    /**
     * store some data as a CouchDB attachment
     * uses PHP socket API
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

        $req = $this->startRequestHeaders('PUT', $url);
        $req .= 'Content-Length: ' . strlen($data) . "\r\n"
            . 'Content-Type: ' . $contentType . "\r\n\r\n";
        $this->connect();
        fwrite($this->socket, $req);
        fwrite($this->socket, $data);
        $response = '';
        while (!feof($this->socket))
            $response .= fgets($this->socket);
        $this->disconnect();
        return $response;
    }

}
