#!/bin/sh
export NAME=phponcouch_test_db
docker stop $NAME
docker rm $NAME
sh _resetDB.sh