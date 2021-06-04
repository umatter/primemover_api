# Setup
## Installation
- https://www.digitalocean.com/community/tutorials/how-to-install-linux-apache-mysql-php-lamp-stack-ubuntu-18-04-de
- Use Virtual Host Template provided below
- Install Composer
- Install Git commands
- Clone API Repository into vhost-root folder (/var/www/primemover.example)
- Change to Repository dir
- Rename `env.example` to `.env`
- Modify `.env` (db access)
- Run: `composer install` and resolve dependencies/issues
- Run: `php artisan down`
- Run: `php artisan migrate --seed`
- Run: `php artisan optimize:clear`
- Run: `php artisan key:generate`
- Run: `php artisan up`
- Make sure directories `storage` and `bootstrap` are writable
- Access API URL to test access `https://primemover.example/api/v1`

## Virtual Host Template
    <VirtualHost *:80>
        ServerAdmin webmaster@primemover.example
        ServerName primemover.example
        ServerAlias www.primemover.example
        DocumentRoot /var/www/primemover.example/public
        ErrorLog ${APACHE_LOG_DIR}/error.log
        CustomLog ${APACHE_LOG_DIR}/access.log combined
    
        <Directory "/var/www/primemover.example">
            Options Indexes FollowSymLinks
            AllowOverride All
            Require all granted
        </Directory>
    
        RewriteEngine on
        RewriteCond %{SERVER_NAME} =www.primemover.example [OR]
        RewriteCond %{SERVER_NAME} =primemover.example
        RewriteRule ^ https://%{SERVER_NAME}%{REQUEST_URI} [END,NE,R=permanent]
    </VirtualHost>
