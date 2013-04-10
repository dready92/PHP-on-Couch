<?php
/**
 * Setup autoloading
 */

require_once __DIR__ . '/../autoload_function.php';

spl_autoload_register('phponcouch_autoload');
spl_autoload_register('phponcouch_test_autoload');