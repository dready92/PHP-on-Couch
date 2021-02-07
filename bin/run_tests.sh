#!/usr/bin/env bash

vendor/bin/phpunit --coverage-clover=coverage.xml --configuration ./phpunit.xml  --testsuite Ordered
php vendor/phpcheckstyle/phpcheckstyle/run.php --src src --config ./php-on-couch-style.xml --format console