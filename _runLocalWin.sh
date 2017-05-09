#!/bin/sh
composer install
composer dump-autoload
docker stop $(docker ps -a -q)
docker rm $(docker ps -a -q)
docker run -d -it -p 5984:5984 klaemo/couchdb:latest & sleep 5
curl -X PUT http://localhost:5984/_users  && curl -X PUT http://localhost:5984/_replicator && curl -X PUT http://localhost:5984/_global_changes
php ./tests/_config/_setupEnvironment.php "nonode@nohost"
vendor/bin/phpunit.bat --coverage-clover=coverage.xml --configuration ./phpunit.xml  --testsuite Ordered
php vendor/phpcheckstyle/phpcheckstyle/run.php --src src --config ./php-on-couch-style.xml
