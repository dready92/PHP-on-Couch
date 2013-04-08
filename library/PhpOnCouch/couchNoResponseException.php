<?php

class couchNoResponseException extends couchException {
    function __construct() {
        parent::__construct(array('status_message'=>'No response from server - '));
    }
}
