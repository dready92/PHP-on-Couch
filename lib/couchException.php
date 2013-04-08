<?php

/**
 * customized Exception class for CouchDB errors
 *
 * this class uses : the Exception message to store the HTTP message sent by the server
 * the Exception code to store the HTTP status code sent by the server
 * and adds a method getBody() to fetch the body sent by the server (if any)
 *
 */
class couchException extends Exception {
    // CouchDB response codes we handle specialized exceptions
    protected static $code_subtypes = array(404=>'couchNotFoundException', 403=>'couchForbiddenException', 401=>'couchUnauthorizedException', 417=>'couchExpectationException');
    // more precise response problem
    protected static $status_subtypes = array('Conflict'=>'couchConflictException');
    // couchDB response once parsed
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


    public static function factory($response, $method, $url, $parameters) {
        if (is_string($response)) $response = couch::parseRawResponse($response);
        if (!$response) return new couchNoResponseException();
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
