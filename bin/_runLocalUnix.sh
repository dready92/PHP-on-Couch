#!/bin/sh
export DIRECTORY=`dirname $0`
export NAME=phponcouch_test_db
sudo docker stop $NAME
sudo docker rm $NAME
sudo sh "$DIRECTORY/_resetDB.sh"
