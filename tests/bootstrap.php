<?php

/*
 * Determine the root, library, and tests directories of the framework
* distribution.
*/
$rootDir        = realpath(dirname(__DIR__));
$coreLibraryDir = "$rootDir/lib";
$coreTestsDir   = "$rootDir/tests";

/*
 * Prepend the Zend Framework library/ and tests/ directories to the
* include_path. This allows the tests to run out of the box and helps prevent
* loading other copies of the framework code and tests that would supersede
* this copy.
*/
$path = array(
        $coreLibraryDir,
        $coreTestsDir,
        get_include_path(),
);
set_include_path(implode(PATH_SEPARATOR, $path));


require '_autoload.php';

unset($rootDir,$coreLibraryDir,$coreTestsDir,$path);
