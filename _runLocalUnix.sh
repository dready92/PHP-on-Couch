#!/bin/sh

export NAME=phponcouch_test_db
sudo docker stop $NAME
sudo docker rm $NAME
sudo sh _resetDB.sh
