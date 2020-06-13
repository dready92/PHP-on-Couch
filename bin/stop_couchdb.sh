#!/usr/bin/env bash

TEST_DOCKER_IMAGE_NAME="${TEST_DOCKER_IMAGE_NAME:-phponcouch_test_db}"
echo "Stopping CouchDB 3 Docker with name: $TEST_DOCKER_IMAGE_NAME"
docker stop $TEST_DOCKER_IMAGE_NAME
docker rm $TEST_DOCKER_IMAGE_NAME