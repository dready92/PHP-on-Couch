<?php

class PhpOnCouch_Exception_NoResponseException extends PhpOnCouch_Exception_Exception {
    function __construct() {
        parent::__construct(array('status_message'=>'No response from server - '));
    }
}
