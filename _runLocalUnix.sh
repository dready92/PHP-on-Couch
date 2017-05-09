#!/bin/sh
composer dump-autoload
sudo docker stop $(sudo docker ps -a -q)
sudo docker rm $(sudo docker ps -a -q)
docker run -d -it -p 5984:5984 klaemo/couchdb:latest & sleep 5
curl -X PUT http://127.0.0.1:5984/_users  && curl -X PUT http://127.0.0.1:5984/_replicator && curl -X PUT http://127.0.0.1:5984/_global_changes
php ./tests/_config/_setupEnvironment.php "nonode@nohost"
vendor/bin/phpunit --coverage-clover=coverage.xml --configuration ./phpunit.xml  --testsuite Ordered
php vendor/phpcheckstyle/phpcheckstyle/run.php --src src --config ./php-on-couch-style.xml
