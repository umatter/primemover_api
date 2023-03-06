#!/bin/bash

echo "Composer install"
composer update
composer install

echo "Setup laravel"
cp /var/www/.env.example /var/www/.env
php artisan optimize:clear
# php artisan l5-swagger:generate

#echo "Migrate database"
#until php artisan migrate:fresh #--seed
#do
#  echo "Waiting for database connection..."
#  sleep 5
#done

# Enable if you want to use dummy data
# php artisan db:seed --class=LocalDevelopmentSeeder

#export HTTPD_SERVER_NAME=${HTTPD_SERVER_NAME:-aprendo:8080}
#export HTTPD_SERVER_NAME=localhost
#echo "ServerName ${HTTPD_SERVER_NAME}" > /opt/app-root/etc/conf.d/99-server-name.conf

#update-ca-trust

#echo "Execute s2i run script"
#/usr/libexec/s2i/run