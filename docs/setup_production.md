# Production Deployment Guide

This guide covers deploying the Primemover API to a production environment with security and performance best practices.

## Prerequisites

Before deploying to production:

- Server with Ubuntu 22.04 LTS (or similar)
- Domain name configured with DNS
- SSL certificate (Let's Encrypt recommended)
- Database server (MySQL 8.0+)
- Redis server for caching and queues
- AWS S3 bucket for file storage
- Sufficient server resources (recommended: 4GB+ RAM, 2+ CPU cores)

## Production Environment Setup

### 1. Server Preparation

```bash
# Update system
sudo apt update && sudo apt upgrade -y

# Install required packages
sudo apt install -y software-properties-common apt-transport-https

# Add PHP repository
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update

# Install PHP 8.1 and extensions
sudo apt install -y php8.1-fpm php8.1-mysql php8.1-redis php8.1-curl \
                    php8.1-xml php8.1-mbstring php8.1-zip php8.1-gd \
                    php8.1-bcmath php8.1-intl php8.1-soap

# Install Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
sudo chmod +x /usr/local/bin/composer

# Install MySQL 8.0
sudo apt install -y mysql-server

# Install Redis
sudo apt install -y redis-server

# Install Nginx
sudo apt install -y nginx
```

### 2. Application Deployment

```bash
# Create application directory
sudo mkdir -p /var/www/primemover-api
cd /var/www/primemover-api

# Clone repository
sudo git clone <repository-url> .

# Set ownership
sudo chown -R www-data:www-data /var/www/primemover-api

# Install dependencies
sudo -u www-data composer install --no-dev --optimize-autoloader

# Set permissions
sudo chmod -R 755 /var/www/primemover-api
sudo chmod -R 775 /var/www/primemover-api/storage
sudo chmod -R 775 /var/www/primemover-api/bootstrap/cache
```

### 3. Environment Configuration

Create production `.env` file:

```bash
sudo -u www-data cp .env.example .env
sudo -u www-data nano .env
```

**Critical production settings:**

```bash
# Application
APP_NAME="Primemover API"
APP_ENV=production
APP_KEY=  # Generate with: php artisan key:generate
APP_DEBUG=false  # MUST be false in production
APP_URL=https://your-domain.com

# Logging
LOG_CHANNEL=daily
LOG_LEVEL=error
LOG_DAYS=14

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=primemover
DB_USERNAME=primemover_user
DB_PASSWORD=STRONG_SECURE_PASSWORD_HERE

# Redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=STRONG_REDIS_PASSWORD
REDIS_PORT=6379

# Cache & Queue
CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

# AWS S3
AWS_DEFAULT_REGION=eu-central-1
AWS_BUCKET=your-production-bucket
AWS_ACCESS_KEY_ID=YOUR_ACCESS_KEY
AWS_SECRET_ACCESS_KEY=YOUR_SECRET_KEY
AWS_USE_PATH_STYLE_ENDPOINT=false

# JWT
JWT_TTL=1440
JWT_SECRET=  # Generate with: php artisan jwt:secret --force

# Telescope (disable in production or restrict access)
TELESCOPE_ENABLED=false

# API Documentation
L5_SWAGGER_GENERATE_ALWAYS=false  # MUST be false in production
L5_SWAGGER_CONST_HOST=https://your-domain.com

# Rate Limiting
THROTTLE_WHITELIST=  # Only add trusted IPs

# CORS
URL_FRONTEND=https://your-frontend-domain.com

# IPStack (optional)
IPSTACK_API_KEY=your_api_key
IPSTACK_DEFAULT_LANGUAGE=en
```

### 4. Database Setup

```bash
# Secure MySQL installation
sudo mysql_secure_installation

# Create database and user
sudo mysql
```

```sql
CREATE DATABASE primemover CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'primemover_user'@'localhost' IDENTIFIED BY 'STRONG_SECURE_PASSWORD';
GRANT ALL PRIVILEGES ON primemover.* TO 'primemover_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

### 5. Application Initialization

```bash
# Generate application key
sudo -u www-data php artisan key:generate

# Generate JWT secret
sudo -u www-data php artisan jwt:secret --force

# Run migrations
sudo -u www-data php artisan migrate --force

# Install Passport OAuth
sudo -u www-data php artisan passport:install --force

# Generate API documentation
sudo -u www-data php artisan l5-swagger:generate

# Cache configuration (important for performance)
sudo -u www-data php artisan config:cache
sudo -u www-data php artisan route:cache
sudo -u www-data php artisan view:cache
```

## Web Server Configuration

### Nginx Configuration

Create Nginx configuration:

```bash
sudo nano /etc/nginx/sites-available/primemover-api
```

**Basic Nginx configuration:**

```nginx
server {
    listen 80;
    listen [::]:80;
    server_name your-domain.com www.your-domain.com;

    # Redirect to HTTPS
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name your-domain.com www.your-domain.com;

    root /var/www/primemover-api/public;
    index index.php;

    # SSL Configuration
    ssl_certificate /etc/letsencrypt/live/your-domain.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/your-domain.com/privkey.pem;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers HIGH:!aNULL:!MD5;
    ssl_prefer_server_ciphers on;

    # Security Headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header Referrer-Policy "no-referrer-when-downgrade" always;
    add_header Content-Security-Policy "default-src 'self' http: https: data: blob: 'unsafe-inline'" always;
    add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;

    # Logging
    access_log /var/log/nginx/primemover-api-access.log;
    error_log /var/log/nginx/primemover-api-error.log;

    # Gzip Compression
    gzip on;
    gzip_vary on;
    gzip_proxied any;
    gzip_comp_level 6;
    gzip_types text/plain text/css text/xml text/javascript application/json application/javascript application/xml+rss application/rss+xml font/truetype font/opentype application/vnd.ms-fontobject image/svg+xml;

    # Client upload limit
    client_max_body_size 100M;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }

    # Block access to sensitive files
    location ~ /(\.env|\.git|composer\.(json|lock)|package(-lock)?\.json) {
        deny all;
    }
}
```

Enable the site:

```bash
# Enable site
sudo ln -s /etc/nginx/sites-available/primemover-api /etc/nginx/sites-enabled/

# Test configuration
sudo nginx -t

# Reload Nginx
sudo systemctl reload nginx
```

### Apache Configuration (Alternative)

If using Apache instead of Nginx:

```bash
sudo nano /etc/apache2/sites-available/primemover-api.conf
```

```apache
<VirtualHost *:80>
    ServerName your-domain.com
    Redirect permanent / https://your-domain.com/
</VirtualHost>

<VirtualHost *:443>
    ServerName your-domain.com
    ServerAdmin admin@your-domain.com
    DocumentRoot /var/www/primemover-api/public

    SSLEngine on
    SSLCertificateFile /etc/letsencrypt/live/your-domain.com/fullchain.pem
    SSLCertificateKeyFile /etc/letsencrypt/live/your-domain.com/privkey.pem

    <Directory /var/www/primemover-api/public>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/primemover-api-error.log
    CustomLog ${APACHE_LOG_DIR}/primemover-api-access.log combined
</VirtualHost>
```

Enable and restart:

```bash
sudo a2ensite primemover-api
sudo a2enmod rewrite ssl
sudo systemctl restart apache2
```

## SSL Certificate Setup

### Using Let's Encrypt (Recommended)

```bash
# Install Certbot
sudo apt install -y certbot python3-certbot-nginx

# Obtain certificate
sudo certbot --nginx -d your-domain.com -d www.your-domain.com

# Test auto-renewal
sudo certbot renew --dry-run
```

## Queue Worker Setup

Configure Laravel queue worker as a systemd service:

```bash
sudo nano /etc/systemd/system/primemover-queue.service
```

```ini
[Unit]
Description=Primemover Queue Worker
After=network.target

[Service]
Type=simple
User=www-data
Group=www-data
Restart=always
RestartSec=3
ExecStart=/usr/bin/php /var/www/primemover-api/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
StandardOutput=append:/var/log/primemover-queue.log
StandardError=append:/var/log/primemover-queue-error.log

[Install]
WantedBy=multi-user.target
```

Enable and start:

```bash
sudo systemctl enable primemover-queue
sudo systemctl start primemover-queue
sudo systemctl status primemover-queue
```

## Scheduled Tasks (Cron)

Add Laravel scheduler to cron:

```bash
sudo -u www-data crontab -e
```

Add:

```cron
* * * * * cd /var/www/primemover-api && php artisan schedule:run >> /dev/null 2>&1
```

## Security Hardening

### 1. Firewall Configuration

```bash
# Install UFW
sudo apt install -y ufw

# Allow SSH
sudo ufw allow 22/tcp

# Allow HTTP/HTTPS
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp

# Enable firewall
sudo ufw enable
sudo ufw status
```

### 2. Secure Redis

```bash
sudo nano /etc/redis/redis.conf
```

```conf
# Bind to localhost only
bind 127.0.0.1 ::1

# Require password
requirepass YOUR_STRONG_REDIS_PASSWORD

# Disable dangerous commands
rename-command FLUSHDB ""
rename-command FLUSHALL ""
rename-command CONFIG ""
```

Restart Redis:

```bash
sudo systemctl restart redis-server
```

### 3. Secure MySQL

```bash
sudo mysql_secure_installation
```

Answer yes to all questions.

### 4. Remove Default Users

```bash
# Access tinker
cd /var/www/primemover-api
sudo -u www-data php artisan tinker

# List default users
User::whereIn('email', ['admin@primemover.example', 'researcher@primemover.example', 'runner@primemover.example'])->get();

# Delete or change passwords
User::where('email', 'admin@primemover.example')->delete();
```

## Monitoring and Logging

### 1. Log Rotation

```bash
sudo nano /etc/logrotate.d/primemover-api
```

```conf
/var/www/primemover-api/storage/logs/*.log {
    daily
    missingok
    rotate 14
    compress
    delaycompress
    notifempty
    create 0640 www-data www-data
    sharedscripts
}
```

### 2. Performance Monitoring

```bash
# Install monitoring tools
sudo apt install -y htop iotop
```

### 3. Application Monitoring

Consider using:
- **Laravel Telescope** (with IP restriction)
- **New Relic** or **Datadog** for APM
- **Sentry** for error tracking
- **CloudWatch** for AWS infrastructure

## Backup Strategy

### 1. Database Backups

Create backup script:

```bash
sudo nano /usr/local/bin/backup-primemover-db.sh
```

```bash
#!/bin/bash
BACKUP_DIR="/var/backups/primemover"
DATE=$(date +%Y%m%d_%H%M%S)
mkdir -p $BACKUP_DIR

mysqldump -u primemover_user -p'PASSWORD' primemover | gzip > $BACKUP_DIR/primemover_$DATE.sql.gz

# Keep only last 30 days
find $BACKUP_DIR -name "primemover_*.sql.gz" -mtime +30 -delete
```

Make executable and add to cron:

```bash
sudo chmod +x /usr/local/bin/backup-primemover-db.sh
sudo crontab -e
```

```cron
0 2 * * * /usr/local/bin/backup-primemover-db.sh
```

### 2. Application Backups

Backup code and configuration:

```bash
#!/bin/bash
tar -czf /var/backups/primemover-app-$(date +%Y%m%d).tar.gz \
  --exclude='storage/logs/*' \
  --exclude='storage/app/public/*' \
  --exclude='node_modules' \
  /var/www/primemover-api
```

## Deployment Updates

When deploying updates:

```bash
# Enter maintenance mode
sudo -u www-data php artisan down

# Pull latest code
sudo -u www-data git pull origin master

# Install/update dependencies
sudo -u www-data composer install --no-dev --optimize-autoloader

# Run migrations
sudo -u www-data php artisan migrate --force

# Clear and rebuild caches
sudo -u www-data php artisan config:clear
sudo -u www-data php artisan config:cache
sudo -u www-data php artisan route:cache
sudo -u www-data php artisan view:cache

# Regenerate API documentation
sudo -u www-data php artisan l5-swagger:generate

# Restart queue workers
sudo systemctl restart primemover-queue

# Exit maintenance mode
sudo -u www-data php artisan up
```

## Performance Optimization

### 1. OPcache Configuration

```bash
sudo nano /etc/php/8.1/fpm/conf.d/10-opcache.ini
```

```ini
opcache.enable=1
opcache.memory_consumption=256
opcache.interned_strings_buffer=16
opcache.max_accelerated_files=20000
opcache.revalidate_freq=2
opcache.fast_shutdown=1
```

### 2. PHP-FPM Optimization

```bash
sudo nano /etc/php/8.1/fpm/pool.d/www.conf
```

```ini
pm = dynamic
pm.max_children = 50
pm.start_servers = 10
pm.min_spare_servers = 5
pm.max_spare_servers = 20
pm.max_requests = 500
```

Restart PHP-FPM:

```bash
sudo systemctl restart php8.1-fpm
```

## Health Checks

Create health check endpoint monitoring:

```bash
# Check API is responding
curl -f https://your-domain.com/api/v1/ip || echo "API Down!"

# Check database connection
sudo -u www-data php /var/www/primemover-api/artisan tinker --execute="DB::connection()->getPdo();"
```

## Related Documentation

- [Installation Guide](installation.md) - Basic setup instructions
- [Troubleshooting](troubleshooting.md) - Common issues
- [Security Guide](https://laravel.com/docs/10.x/deployment#server-requirements) - Laravel security best practices
