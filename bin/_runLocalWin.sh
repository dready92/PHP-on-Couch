#!/bin/sh
export NAME=phponcouch_test_db
docker stop $NAME
docker rm $NAME
sh "${BASH_SOURCE%/*}/_resetDB.sh"