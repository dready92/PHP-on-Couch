<?php

if ( !defined('PHPONCOUCH_LIBRARY_PATH') ) {
    define('PHPONCOUCH_LIBRARY_PATH', __DIR__ . '/lib/');
}

function phponcouch_autoload ($className)
{
    $classFile = PHPONCOUCH_LIBRARY_PATH . str_replace(array('_','\\'), '/', $className ) . '.php';
    if ( !file_exists($classFile) ) return false;

    require_once $classFile;

    return class_exists($className,false);
}
