#!/bin/sh

#Composer update
composer install
composer dump-autoload

#Docker image setup
NAME="${NAME:-phponcouch_test_db}"
echo "Starting a container with name : $NAME"
export DB_PORT="${DB_PORT:-5984}"
export DB_HOST="${DB_HOST:-localhost}"
DSN="http://$DB_HOST:$DB_PORT"
docker run -d -it -p $DB_PORT:5984 --name $NAME klaemo/couchdb:latest & sleep 5
curl -X PUT $DSN/_users  && curl -X PUT $DSN/_replicator && curl -X PUT $DSN/_global_changes
php ./tests/_config/_setupEnvironment.php "nonode@nohost"
vendor/bin/phpunit --coverage-clover=coverage.xml --configuration ./phpunit.xml  --testsuite Ordered
php vendor/phpcheckstyle/phpcheckstyle/run.php --src src --config ./php-on-couch-style.xml
