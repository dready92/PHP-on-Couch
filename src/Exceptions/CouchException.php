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

use Exception,
	InvalidArgumentException;

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
	protected static $code_subtypes = array(404 => 'CouchNotFoundException', 403 => 'CouchForbiddenException', 401 => 'CouchUnauthorizedException', 417 => 'CouchExpectationException');
	// more precise response problem
	protected static $status_subtypes = array('Conflict' => 'CouchConflictException');
	// couchDB response once parsed
	protected $couch_response = array();

	/**
	 * class constructor
	 *
	 * @param string|array $response HTTP response from the CouchDB server
	 * @param string $method  the HTTP method
	 * @param string $url the target URL
	 * @param mixed $parameters the query parameters
	 */
	function __construct($response, $method = null, $url = null, $parameters = null)
	{
		$this->couch_response = is_string($response) ? self::parseRawResponse($response) : $response;
		if (is_object($this->couch_response['body']) and isset($this->couch_response['body']->reason))
			$message = $this->couch_response['status_message'] . ' - ' . $this->couch_response['body']->reason;
		else
			$message = $this->couch_response['status_message'];
		if ($method)
			$message.= " ($method $url " . json_encode($parameters) . ')';
		parent::__construct($message, isset($this->couch_response['status_code']) ? $this->couch_response['status_code'] : null);
	}

	public static function factory($response, $method, $url, $parameters)
	{
		if (is_string($response))
			$response = self::parseRawResponse($response);
		if (!$response)
			return new CouchNoResponseException();
		if (isset($response['status_code']) and isset(self::$code_subtypes[$response['status_code']])) {
			$class = __NAMESPACE__ . '\\' . self::$code_subtypes[$response['status_code']];
			return new $class($response, $method, $url, $parameters);
		} elseif (isset($response['status_message']) and isset(self::$status_subtypes[$response['status_message']])) {
			$class = __NAMESPACE__ . '\\' . self::$status_subtypes[$response['status_message']];
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
		if (isset($this->couch_response['body']))
			return $this->couch_response['body'];
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
	 * @param string $raw_data data sent back by the server
	 * @param boolean $json_as_array is true, the json response will be decoded as an array. Is false, it's decoded as an object
	 * @return array CouchDB response
	 * @throws InvalidArgumentException
	 */
	public static function parseRawResponse($raw_data, $json_as_array = FALSE)
	{
		if (!strlen($raw_data))
			throw new InvalidArgumentException("no data to parse");
		while (!substr_compare($raw_data, "HTTP/1.1 100 Continue\r\n\r\n", 0, 25)) {
			$raw_data = substr($raw_data, 25);
		}
		$response = array('body' => null);
		list($headers, $body) = explode("\r\n\r\n", $raw_data, 2);
		$headers_array = explode("\n", $headers);
		$status_line = reset($headers_array);
		$status_array = explode(' ', $status_line, 3);
		$response['status_code'] = trim($status_array[1]);
		$response['status_message'] = trim($status_array[2]);
		if (strlen($body)) {
			$response['body'] = preg_match('@Content-Type:\s+application/json@i', $headers) ? json_decode($body, $json_as_array) : $body;
		}
		return $response;
	}

}

/**
 * Description of couchExpectationException
 *
 */
//class couchExpectationException extends CouchException
//{
//	//put your code here
//}





