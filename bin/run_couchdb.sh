#!/usr/bin/env bash

# Install CouchDB Master
TEST_DOCKER_IMAGE_NAME="${TEST_DOCKER_IMAGE_NAME:-phponcouch_test_db}"
DSN=http://admin:password@127.0.0.1:5984

echo "Starting CouchDB 3 Docker with name: $TEST_DOCKER_IMAGE_NAME"
docker run --ulimit nofile=2048:2048 -d -p 5984:5984 \
    --env COUCHDB_USER=admin --env COUCHDB_PASSWORD=password \
    --name $TEST_DOCKER_IMAGE_NAME \
    couchdb

# wait for couchdb to start
while [ '200' != $(curl -s -o /dev/null -w %{http_code} $DSN/_all_dbs) ]; do
  echo waiting for couch to load... ;
  sleep 1;
done

curl -X PUT $DSN/_users  && curl -X PUT $DSN/_replicator

curl -X PUT $DSN/_users/org.couchdb.user:client \
     -H "Accept: application/json" \
     -H "Content-Type: application/json" \
     -d '{"name": "client", "password": "client", "roles": [], "type": "user"}'