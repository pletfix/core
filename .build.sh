#!/bin/sh

echo 'APP_ENV=testing' > .env

mkdir storage && mkdir storage/cache && mkdir storage/db && mkdir storage/logs && mkdir storage/sessions && mkdir storage/temp && mkdir storage/upload
chmod 2777 storage/*
touch storage/db/sqlite.db

composer install
