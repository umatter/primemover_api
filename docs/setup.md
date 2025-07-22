# Setup

This guide covers both traditional server setup and Docker-based development setup for the Primemover API.

## Requirements

- **PHP**: 8.1 or higher
- **Laravel**: 10.x
- **Database**: MySQL 8.0+ or PostgreSQL
- **Cache/Queue**: Redis
- **Storage**: AWS S3 (for file uploads)
- **Web Server**: Apache or Nginx

## Option 1: Traditional Server Setup

### 1. System Requirements

**Ubuntu 22.04 LTS (recommended):**
```bash
# Update system
sudo apt update && sudo apt upgrade -y

# Install PHP 8.1 and required extensions
sudo apt install php8.1 php8.1-fpm php8.1-mysql php8.1-redis php8.1-zip php8.1-gd php8.1-mbstring php8.1-curl php8.1-xml php8.1-bcmath php8.1-json -y

# Install Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Install MySQL 8.0
sudo apt install mysql-server -y

# Install Redis
sudo apt install redis-server -y

# Install Apache or Nginx
sudo apt install apache2 -y
# OR
sudo apt install nginx -y
```

### 2. Clone and Configure Application

```bash
# Clone repository
git clone <repository-url> /var/www/primemover-api
cd /var/www/primemover-api

# Set permissions
sudo chown -R www-data:www-data /var/www/primemover-api
sudo chmod -R 755 /var/www/primemover-api
sudo chmod -R 775 /var/www/primemover-api/storage
sudo chmod -R 775 /var/www/primemover-api/bootstrap/cache

# Install dependencies
composer install --optimize-autoloader --no-dev

# Environment setup
cp .env.example .env
```

### 3. Environment Configuration

Edit `.env` file with your settings:

```bash
# Application
APP_NAME="Primemover API"
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=https://pm-api.test.primemover.example

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=primemover
DB_USERNAME=primemover_user
DB_PASSWORD=your_secure_password

# Redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=your_redis_password
REDIS_PORT=6379

# Queue
QUEUE_CONNECTION=redis

# AWS S3 (Required for file uploads)
AWS_DEFAULT_REGION=eu-central-1
AWS_BUCKET=your-s3-bucket-name
AWS_ACCESS_KEY_ID=your_access_key
AWS_SECRET_ACCESS_KEY=your_secret_key
AWS_USE_PATH_STYLE_ENDPOINT=false

# JWT
JWT_TTL=1440
JWT_SECRET=

# Mail
MAIL_MAILER=smtp
MAIL_HOST=your-smtp-host
MAIL_PORT=587
MAIL_USERNAME=your-email
MAIL_PASSWORD=your-email-password
MAIL_ENCRYPTION=tls
```

### 4. Laravel Application Setup

```bash
# Generate application key
php artisan key:generate

# Generate JWT secret
php artisan jwt:secret --force

# Run database migrations
php artisan migrate --force

# Seed database (optional)
php artisan db:seed

# Install Laravel Passport
php artisan passport:install --force

# Create storage link
php artisan storage:link

# Clear and cache configuration
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### 5. Web Server Configuration

**Apache Virtual Host:**
```apache
<VirtualHost *:80>
    ServerName pm-api.test.primemover.example
    DocumentRoot /var/www/primemover-api/public
    
    <Directory /var/www/primemover-api/public>
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog ${APACHE_LOG_DIR}/primemover_error.log
    CustomLog ${APACHE_LOG_DIR}/primemover_access.log combined
    
    # Redirect to HTTPS
    RewriteEngine on
    RewriteCond %{SERVER_NAME} =pm-api.test.primemover.example
    RewriteRule ^ https://%{SERVER_NAME}%{REQUEST_URI} [END,NE,R=permanent]
</VirtualHost>

<VirtualHost *:443>
    ServerName pm-api.test.primemover.example
    DocumentRoot /var/www/primemover-api/public
    
    <Directory /var/www/primemover-api/public>
        AllowOverride All
        Require all granted
    </Directory>
    
    # SSL Configuration
    SSLEngine on
    SSLCertificateFile /path/to/your/certificate.crt
    SSLCertificateKeyFile /path/to/your/private.key
    
    ErrorLog ${APACHE_LOG_DIR}/primemover_ssl_error.log
    CustomLog ${APACHE_LOG_DIR}/primemover_ssl_access.log combined
</VirtualHost>
```

**Nginx Configuration:**
```nginx
server {
    listen 80;
    server_name pm-api.test.primemover.example;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    server_name pm-api.test.primemover.example;
    root /var/www/primemover-api/public;
    index index.php;

    # SSL Configuration
    ssl_certificate /path/to/your/certificate.crt;
    ssl_certificate_key /path/to/your/private.key;

    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header X-Content-Type-Options "nosniff" always;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

### 6. Queue Worker Setup

Create systemd service for queue workers:

```bash
# Create service file
sudo nano /etc/systemd/system/primemover-worker.service
```

```ini
[Unit]
Description=Primemover Queue Worker
After=redis.service

[Service]
User=www-data
Group=www-data
Restart=always
ExecStart=/usr/bin/php /var/www/primemover-api/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
StandardOutput=journal
StandardError=journal

[Install]
WantedBy=multi-user.target
```

```bash
# Enable and start service
sudo systemctl daemon-reload
sudo systemctl enable primemover-worker
sudo systemctl start primemover-worker
```

### 7. Cron Jobs

Add to crontab for scheduled tasks:

```bash
sudo crontab -e
```

Add this line:
```
* * * * * cd /var/www/primemover-api && php artisan schedule:run >> /dev/null 2>&1
```

### 8. Testing Installation

```bash
# Test API endpoint
curl https://pm-api.test.primemover.example/api/v1/ip

# Check queue worker status
sudo systemctl status primemover-worker

# Check logs
tail -f /var/www/primemover-api/storage/logs/laravel.log
```

## Option 2: Docker Development Setup

### 1. Prerequisites

```bash
# Install Docker and Docker Compose
sudo apt update
sudo apt install docker.io docker-compose -y
sudo usermod -aG docker $USER
# Log out and back in for group changes
```

### 2. Clone and Setup

```bash
# Clone repository
git clone <repository-url> primemover-api
cd primemover-api

# Copy environment file
cp .env.example .env
```

### 3. Configure Environment

Edit `.env` for Docker setup:

```bash
# Database (Docker services)
DB_HOST=mysql
DB_DATABASE=primemover
DB_USERNAME=primemover
DB_PASSWORD=secret

# Redis (Docker service)
REDIS_HOST=redis
REDIS_PASSWORD=secret

# Other settings same as traditional setup
```

### 4. Start Docker Services

```bash
# Start all services
docker-compose up -d

# Install dependencies
docker-compose exec php composer install

# Generate keys
docker-compose exec php php artisan key:generate
docker-compose exec php php artisan jwt:secret --force

# Run migrations
docker-compose exec php php artisan migrate --seed

# Install Passport
docker-compose exec php php artisan passport:install --force

# Create storage link
docker-compose exec php php artisan storage:link
```

### 5. Access Application

- **API**: http://localhost:8080/api/v1/
- **Database**: localhost:3306
- **Redis**: localhost:8889

### 6. Docker Commands

```bash
# View logs
docker-compose logs -f

# Access PHP container
docker-compose exec php bash

# Stop services
docker-compose down

# Rebuild containers
docker-compose up --build -d
```

## Production Deployment Notes

### Security Checklist

1. **Environment Variables**: Never commit `.env` to version control
2. **File Permissions**: Ensure proper ownership and permissions
3. **SSL/TLS**: Always use HTTPS in production
4. **Firewall**: Configure firewall rules
5. **Database**: Use strong passwords and restrict access
6. **Backups**: Set up automated database and file backups

### Performance Optimization

1. **OPcache**: Enable PHP OPcache
2. **Redis**: Use Redis for sessions and cache
3. **Queue Workers**: Run multiple queue workers for high load
4. **CDN**: Use CloudFront or similar for static assets
5. **Monitoring**: Set up application monitoring

### Maintenance

```bash
# Update dependencies
composer update

# Clear caches
php artisan optimize:clear

# Restart queue workers
sudo systemctl restart primemover-worker

# Backup database
mysqldump -u root -p primemover > backup_$(date +%Y%m%d_%H%M%S).sql
```

## Troubleshooting

### Common Issues

1. **Permission Errors**: Check file ownership and permissions
2. **Database Connection**: Verify database credentials and service status
3. **Queue Not Processing**: Check Redis connection and worker status
4. **File Upload Issues**: Verify S3 configuration and permissions
5. **SSL Errors**: Check certificate installation and configuration

### Useful Commands

```bash
# Check PHP version and extensions
php -v && php -m

# Test database connection
php artisan tinker
>>> DB::connection()->getPdo();

# Check queue status
php artisan queue:work --once

# View application logs
tail -f storage/logs/laravel.log
```

For additional support, check the Laravel documentation and project-specific issues in the repository.
