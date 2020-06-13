#!/usr/bin/env bash

DIRECTORY=$(dirname $0)

# Dependencies
sh "$DIRECTORY"/install_dependencies.sh

# Database start
sh "$DIRECTORY"/run_couchdb.sh

# Tests
sh "$DIRECTORY"/run_tests.sh

# Database cleanup
sh "$DIRECTORY"/stop_couchdb.sh
