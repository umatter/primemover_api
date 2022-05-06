#!/bin/bash
sudo chown -R phsg-admin /srv/www/admintest.aprendo.ch
cd /srv/www/admintest.aprendo.ch
php artisan backup:run --only-db
php artisan backup:clean
git reset --hard
git reset --soft
git clean -fn
git pull
composer install
php artisan optimize:clear
php artisan storage:link --force
php artisan db:seed --class=CreateSpatiePermissionSeeder --force
php artisan db:seed --class=SpatiePermissionSeeder --force
php artisan db:seed --class=UserSettingsTableSeeder --force
php artisan db:seed --class=RolePermissionUpdateSeeder --force
cp -f docker/htaccess.public /srv/www/admintest.aprendo.ch/public/.htaccess
sudo chown -R www-data:phsg-admin /srv/www/admintest.aprendo.ch
sudo chmod -R 777 storage
sudo chmod -R 777 bootstrap
sudo chown -R phsg-admin .git

