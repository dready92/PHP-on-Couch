<?php

if ( !defined('PHPONCOUCH_LIBRARY_PATH') ) {
    define('PHPONCOUCH_LIBRARY_PATH', __DIR__ . '/lib/');
}

return array(
    'couch'                         => PHPONCOUCH_LIBRARY_PATH . 'couch.php',
    'couchAdmin'                    => PHPONCOUCH_LIBRARY_PATH . 'couchAdmin.php',
    'couchClient'                   => PHPONCOUCH_LIBRARY_PATH . 'couchClient.php',
    'couchDocument'                 => PHPONCOUCH_LIBRARY_PATH . 'couchDocument.php',
    'couchReplicator'               => PHPONCOUCH_LIBRARY_PATH . 'couchReplicator.php',
    'couchException'                => PHPONCOUCH_LIBRARY_PATH . 'couchException.php',
    'couchConflictException'        => PHPONCOUCH_LIBRARY_PATH . 'couchConflictException.php',
    'couchExpectationException'     => PHPONCOUCH_LIBRARY_PATH . 'couchExpectationException.php',
    'couchForbiddenException'       => PHPONCOUCH_LIBRARY_PATH . 'couchForbiddenException.php',
    'couchNoResponseException'      => PHPONCOUCH_LIBRARY_PATH . 'couchNoResponseException.php',
    'couchNotFoundException'        => PHPONCOUCH_LIBRARY_PATH . 'couchNotFoundException.php',
    'couchUnauthorizedException'    => PHPONCOUCH_LIBRARY_PATH . 'couchUnauthorizedException.php',
);
