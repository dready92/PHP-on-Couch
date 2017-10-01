<?php

namespace PHPOnCouch;

use InvalidArgumentException,
    PHPOnCouch\Exceptions,
    PHPUnit_Framework_TestCase,
    stdClass;

require_once join(DIRECTORY_SEPARATOR, [__DIR__, '_config', 'config.php']);

/**
 * @backupGlobals disabled
 * @backupStaticAttributes disabled
 */
class ConfigTest extends PHPUnit_Framework_TestCase
{
}
