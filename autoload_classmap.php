<?php

if ( !defined('PHPONCOUCH_LIBRARY_PATH') ) {
    define('PHPONCOUCH_LIBRARY_PATH', __DIR__ . '/lib/');
}

return array(
    'PhpOnCouch_AbstractClient'                     => PHPONCOUCH_LIBRARY_PATH . '/PhpOnCouch/AbstractClient.php',
    'PhpOnCouch_Admin'                              => PHPONCOUCH_LIBRARY_PATH . '/PhpOnCouch/Admin.php',
    'PhpOnCouch_Client'                             => PHPONCOUCH_LIBRARY_PATH . '/PhpOnCouch/Client.php',
    'PhpOnCouch_Document'                           => PHPONCOUCH_LIBRARY_PATH . '/PhpOnCouch/Document.php',
    'PhpOnCouch_Replicator'                         => PHPONCOUCH_LIBRARY_PATH . '/PhpOnCouch/Replicator.php',
    'PhpOnCouch_Exception_Exception'                => PHPONCOUCH_LIBRARY_PATH . '/PhpOnCouch/Exception/Exception.php',
    'PhpOnCouch_Exception_ConflictException'        => PHPONCOUCH_LIBRARY_PATH . '/PhpOnCouch/Exception/ConflictException.php',
    'PhpOnCouch_Exception_ExpectationException'     => PHPONCOUCH_LIBRARY_PATH . '/PhpOnCouch/Exception/ExpectationException.php',
    'PhpOnCouch_Exception_ForbiddenException'       => PHPONCOUCH_LIBRARY_PATH . '/PhpOnCouch/Exception/ForbiddenException.php',
    'PhpOnCouch_Exception_NoResponseException'      => PHPONCOUCH_LIBRARY_PATH . '/PhpOnCouch/Exception/NoResponseException.php',
    'PhpOnCouch_Exception_NotFoundException'        => PHPONCOUCH_LIBRARY_PATH . '/PhpOnCouch/Exception/NotFoundException.php',
    'PhpOnCouch_Exception_UnauthorizedException'    => PHPONCOUCH_LIBRARY_PATH . '/PhpOnCouch/Exception/UnauthorizedException.php',
);
