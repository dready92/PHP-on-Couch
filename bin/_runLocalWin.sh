#!/bin/sh
export DIRECTORY=`dirname $0`
export NAME=phponcouch_test_db
docker stop $NAME
docker rm $NAME
sh "$DIRECTORY/_resetDB.sh"