#!/usr/bin/env bash

composer self-update
composer install --prefer-source --no-interaction --dev
composer dump-autoload