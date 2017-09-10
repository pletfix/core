#!/bin/sh

mkdir storage && mkdir storage/cache && mkdir storage/db && mkdir storage/temp && mkdir storage/upload
chmod 2777 storage/*
touch storage/db/sqlite.db
echo 'APP_ENV=testing' > .env

composer self-update
composer install
