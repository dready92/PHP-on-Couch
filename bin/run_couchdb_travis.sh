#!/usr/bin/env bash

# Install CouchDB Master
if [ ! -z $TRAVIS ]; then
  echo "Starting CouchDB 3 Docker"
  DSN=http://admin:admin@127.0.0.1:5984
  docker run --ulimit nofile=2048:2048 -d -p 5984:5984 \
      --env COUCHDB_USER=admin --env COUCHDB_PASSWORD=admin \
      couchdb --with-haproxy -n 1

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
fi
