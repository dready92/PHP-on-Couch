<?php

if ( !defined('PHPONCOUCH_LIBRARY_PATH') ) {
    define('PHPONCOUCH_LIBRARY_PATH', __DIR__ . '/library/');
}
if ( !defined('PHPONCOUCH_TEST_PATH') ) {
    define('PHPONCOUCH_TEST_PATH', __DIR__ . '/tests/');
}

function phponcouch_autoload ($className)
{
    if ( !preg_match('^PhpOnCouch_') ) return;

    $classFile = PHPONCOUCH_LIBRARY_PATH . str_replace(array('_','\\'), '/', $className ) . '.php';
    if ( !file_exists($classFile) ) return false;

    require_once $classFile;

    return class_exists($className,false);
}

function phponcouch_test_autoload ($className)
{
    if ( !preg_match('^PhpOnCouchTest_') ) return;

    $classFile = PHPONCOUCH_TESTS_PATH . str_replace(array('_','\\'), '/', $className ) . '.php';
    if ( !file_exists($classFile) ) return false;

    require_once $classFile;

    return class_exists($className,false);
}
