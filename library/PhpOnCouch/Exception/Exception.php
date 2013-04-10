<?PHP
/**
 * Copyright (C) 2009  Mickael Bailly
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * customized Exception class for CouchDB errors
 *
 * this class uses : the Exception message to store the HTTP message sent by the server
 * the Exception code to store the HTTP status code sent by the server
 * and adds a method getBody() to fetch the body sent by the server (if any)
 *
 */
class PhpOnCouch_Exception_Exception extends Exception {
    /**
     * CouchDB response codes we handle specialized exceptions
     * @var array
     */
    protected static $code_subtypes = array(404=>'PhpOnCouch_Exception_NotFoundException',
                                            403=>'PhpOnCouch_Exception_ForbiddenException',
                                            401=>'PhpOnCouch_Exception_UnauthorizedException',
                                            417=>'PhpOnCouch_Exception_ExpectationException');
    /**
     * more precise response problem
     * @var array
     */
    protected static $status_subtypes = array('Conflict'=>'PhpOnCouch_Exception_ConflictException');
    /**
     * couchDB response once parsed
     * @var array
     */
    protected $couch_response = array();

    /**
     *class constructor
     *
     * @param string|array $response HTTP response from the CouchDB server
     * @param string $method  the HTTP method
     * @param string $url the target URL
     * @param mixed $parameters the query parameters
     */
    function __construct($response, $method = null, $url = null, $parameters = null) {
        $this->couch_response = is_string($response) ? couch::parseRawResponse($response) : $response;
        if (is_object($this->couch_response['body']) and isset($this->couch_response['body']->reason))
            $message = $this->couch_response['status_message'] . ' - ' . $this->couch_response['body']->reason;
        else
            $message = $this->couch_response['status_message'];
        if ( $method )	$message.= " ($method $url ".json_encode($parameters).')';
        parent::__construct($message, isset($this->couch_response['status_code']) ? $this->couch_response['status_code'] : null);
    }

    /**
     * factory for response specific exceptions
     *
     * @param string $response
     * @param string $method
     * @param string $url
     * @param string $parameters
     * @return PhpOnCouch_Exception_NoResponseException|PhpOnCouch_Exception_Exception
     */
    public static function factory($response, $method, $url, $parameters) {
        if (is_string($response)) $response = couch::parseRawResponse($response);
        if (!$response) return new PhpOnCouch_Exception_NoResponseException();
        if (isset($response['status_code']) and isset(self::$code_subtypes[$response['status_code']]))
            return new self::$code_subtypes[$response['status_code']]($response, $method, $url, $parameters);
        elseif (isset($response['status_message']) and isset(self::$status_subtypes[$response['status_message']]))
        return new self::$status_subtypes[$response['status_message']]($response, $method, $url, $parameters);
        else
            return new self($response, $method, $url, $parameters);
    }

    /**
     * returns CouchDB server response body (if any)
     *
     * if the response's "Content-Type" is set to "application/json", the
     * body is json_decode()d
     *
     * @return string|object|null CouchDB server response
     */
    function getBody() {
        if ( isset($this->couch_response['body']) )
            return $this->couch_response['body'];
    }
}
