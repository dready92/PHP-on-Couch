<?php


chdir(__DIR__);

$phpunit_bin      = __DIR__ . '/../vendor/bin/phpunit';
$phpunit_bin      = file_exists($phpunit_bin) ? $phpunit_bin : 'phpunit';

$phpunit_conf     = 'phpunit.xml';
$phpunit_conf     = file_exists($phpunit_conf) ? $phpunit_conf : 'phpunit.xml.dist';

$phpunit_opts     = "-c $phpunit_conf";
$phpunit_coverage = '';

if (getenv('PHPUNIT_OPTS') !== false) {
    $phpunit_opts .= ' ' . getenv('PHPUNIT_OPTS');
}

$run_as     = 'paths';
$components = array();


if ($argc == 1) {
    $components = getAll($phpunit_conf);
} else {
    for ($i = 1; $i < $argc; $i++) {
        $arg = $argv[$i];
        switch ($arg) {
            case '-h':
            case '--html':
                $phpunit_coverage = '--coverage-html ' . $argv[++$i];
                break;
            case '-c':
            case '--clover':
                $phpunit_coverage = '--coverage-clover ' . $argv[++$i];
                break;
            case '-g':
            case '--groups':
                $run_as = 'groups';
                break;
            case 'all':
                if ($run_as == 'paths') {
                    $components = getAll($phpunit_conf);
                }
                break;
            default:
                if (strpos($arg, 'PhpOnCouch') !== false) {
                    $components[] = $arg;
                } else {
                    $components[] = 'PhpOnCouch_' . $arg;
                }
        }
    }
}

$result = 0;
if ($run_as == 'groups') {
    $groups = join(',', $components);
    echo "$groups:\n";
    system("$phpunit_bin $phpunit_opts $phpunit_coverage --group " . $groups, $result);
    echo "\n\n";
} else {
    foreach ($components as $component) {
        $component =   'PhpOnCouchTest/' . basename(str_replace('_', '/', $component));
        echo "$component:\n";
        system("$phpunit_bin $phpunit_opts $phpunit_coverage " . escapeshellarg(__DIR__ . '/' . $component), $c_result);
        echo "\n\n";
        if ($c_result) {
            $result = $c_result;
        }
    }
}

exit($result);

// Functions
function getAll($phpunit_conf)
{
    $components = array();
    $conf = simplexml_load_file($phpunit_conf);
    $excludes = $conf->xpath('/phpunit/testsuites/testsuite/exclude/text()');
    for ($i = 0; $i < count($excludes); $i++) {
        $excludes[$i] = basename($excludes[$i]);
    }
    if ($handle = opendir(__DIR__ . '/PhpOnCouchTest/')) {
        while (false !== ($entry = readdir($handle))) {
            if ($entry != '.' && $entry != '..' && !in_array($entry, $excludes)) {
                $components[] = $entry;
            }
        }
        closedir($handle);
    }
    sort($components);
    return $components;
}