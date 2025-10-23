# Troubleshooting Guide

This guide covers common issues and their solutions for the Primemover API.

## Docker Issues

### Port Conflicts

**Problem**: Ports are already in use by other services

**Solution**: Change ports in `docker-compose.yml` or `.env.docker`:

```yaml
# docker-compose.yml
ports:
  - "8091:80"   # Change 8090 to 8091
  - "8444:443"  # Change 8443 to 8444
```

Or update environment variables:
```bash
HOST_MACHINE_UNSECURE_HOST_PORT=8091
HOST_MACHINE_SECURE_HOST_PORT=8444
```

### Permission Errors

**Problem**: Permission denied errors when accessing files

**Solution**: Fix ownership and permissions:

```bash
# Change ownership
sudo chown -R $USER:$USER .

# Fix permissions for storage
chmod -R 775 storage
chmod -R 775 bootstrap/cache
```

For Docker specifically:
```bash
# Run from host machine
docker-compose exec php chown -R www-data:www-data storage bootstrap/cache
docker-compose exec php chmod -R 775 storage bootstrap/cache
```

### Apache Keeps Restarting

**Problem**: Apache container constantly restarts

**Causes & Solutions**:

1. **Started single service instead of all**:
   ```bash
   # WRONG:
   docker-compose up -d apache

   # CORRECT:
   docker-compose up -d --build
   ```

2. **Configuration errors**:
   ```bash
   # Check logs
   docker-compose logs -f apache

   # Look for errors in PHP logs too
   docker-compose logs -f php
   ```

3. **Port conflicts** - see Port Conflicts section above

### Telescope Shows 404

**Problem**: Cannot access `/telescope` endpoint

**Solution**:

1. Ensure Telescope is enabled in `.env`:
   ```bash
   TELESCOPE_ENABLED=true
   ```

2. Clear config cache:
   ```bash
   docker-compose exec php php artisan config:clear
   ```

3. Check routes:
   ```bash
   docker-compose exec php php artisan route:list | grep telescope
   ```

### Container Won't Start

**Problem**: Container fails to start

**Solution**:

1. **Check logs** for specific error:
   ```bash
   docker-compose logs container-name
   ```

2. **Check Docker resources**:
   - Ensure enough memory allocated (at least 2GB)
   - Check disk space

3. **Rebuild from scratch**:
   ```bash
   docker-compose down -v
   docker-compose build --no-cache
   docker-compose up -d
   ```

4. **Check for port conflicts**:
   ```bash
   # See what's using port 8090
   sudo lsof -i :8090
   ```

## Native Setup Issues

### Database Connection Errors

**Problem**: Cannot connect to MySQL database

**Solutions**:

1. **Verify MySQL is running**:
   ```bash
   sudo systemctl status mysql
   # or
   sudo service mysql status
   ```

2. **Start MySQL if stopped**:
   ```bash
   sudo systemctl start mysql
   # or
   sudo service mysql start
   ```

3. **Check credentials in `.env`** are correct:
   ```bash
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=primemover
   DB_USERNAME=primemover
   DB_PASSWORD=secret
   ```

4. **Test connection manually**:
   ```bash
   mysql -h 127.0.0.1 -u primemover -p
   ```

5. **Verify database exists**:
   ```sql
   SHOW DATABASES;
   ```

### Permission Denied

**Problem**: Cannot write to storage or cache directories

**Solution**:

```bash
# Fix permissions
chmod -R 755 storage
chmod -R 755 bootstrap/cache

# Fix ownership
sudo chown -R www-data:www-data storage bootstrap/cache
# Or for development:
sudo chown -R $USER:$USER storage bootstrap/cache
```

### Missing PHP Extensions

**Problem**: Required PHP extensions not installed

**Solution**:

```bash
# Ubuntu/Debian
sudo apt install php8.1-mysql php8.1-redis php8.1-curl php8.1-xml \
                 php8.1-mbstring php8.1-zip php8.1-gd php8.1-bcmath

# Verify extensions
php -m | grep -E 'mysql|redis|curl|xml|mbstring'
```

### Composer Errors

**Problem**: Composer install/update failures

**Solutions**:

1. **Update Composer**:
   ```bash
   composer self-update
   ```

2. **Clear Composer cache**:
   ```bash
   composer clear-cache
   ```

3. **Install with no scripts** (if post-install scripts fail):
   ```bash
   composer install --no-scripts
   php artisan key:generate
   composer run post-install-cmd
   ```

4. **For production**:
   ```bash
   composer install --no-dev --optimize-autoloader
   ```

## Configuration Issues

### "Config is not set or empty" Error

**Problem**: Throttle whitelist configuration error

**Solutions**:

1. **Add to `.env` file**:
   ```bash
   THROTTLE_WHITELIST=127.0.0.1,localhost
   ```

2. **Clear config cache**:
   ```bash
   # Docker
   docker-compose exec php php artisan config:clear

   # Native
   php artisan config:clear
   ```

3. **Restart containers** (Docker):
   ```bash
   docker-compose down
   docker-compose up -d
   ```

### Changes in .env Not Taking Effect

**Problem**: Modified `.env` values not being used

**Solution**:

```bash
# Always clear config cache after .env changes
php artisan config:clear

# For Docker
docker-compose exec php php artisan config:clear
docker-compose restart
```

### "Key Not Found" Errors

**Problem**: APP_KEY or JWT_SECRET not set

**Solution**:

```bash
# Generate application key
php artisan key:generate

# Generate JWT secret
php artisan jwt:secret --force

# Clear config
php artisan config:clear
```

## Authentication Issues

### "Invalid Credentials" Error

**Problem**: Cannot log in with correct credentials

**Causes & Solutions**:

1. **Using bcrypt hash instead of plain text password**:
   ```bash
   # WRONG:
   {"password": "$2y$10$92IXUNpkjO0rOQ5byMi.Ye4..."}

   # CORRECT:
   {"password": "password"}
   ```

2. **Verify user exists**:
   ```bash
   docker-compose exec php php artisan tinker
   >>> User::where('email', 'admin@primemover.example')->first();
   ```

3. **Check using HTTP not HTTPS** for local:
   ```bash
   # Use http:// not https://
   curl http://localhost:8090/api/v1/login
   ```

### Token Expired

**Problem**: Authentication token no longer works

**Solution**:

Get a new token by logging in again:
```bash
curl -X POST 'http://localhost:8090/api/v1/login' \
  -H 'Content-Type: application/json' \
  -d '{"email": "admin@primemover.example", "password": "password"}'
```

**Note**: Default JWT_TTL is 1440 minutes (24 hours)

### Invalid Token Format

**Problem**: 401 Unauthorized with valid token

**Solution**:

Ensure Bearer token is formatted correctly:
```bash
# CORRECT:
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc...

# WRONG:
Authorization: eyJ0eXAiOiJKV1QiLCJhbGc...
Authorization: Bearer: eyJ0eXAiOiJKV1QiLCJhbGc...
```

## API Issues

### 401 Unauthorized

**Problem**: API returns 401 error

**Causes & Solutions**:

1. **Missing Authorization header**:
   ```bash
   # Add Authorization header
   curl -H "Authorization: Bearer YOUR_TOKEN" http://localhost:8090/api/v1/experiments
   ```

2. **Token expired** - Get new token (see Token Expired section)

3. **Invalid token format** - Check Bearer prefix (see above)

### 422 Validation Error

**Problem**: Request validation fails

**Solution**:

1. **Check error response** for specific field errors
2. **Review required fields** in [JSON Examples](examples.md)
3. **Verify data types** match expectations
4. **Check for missing nested objects**

Example error response:
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "name": ["The name field is required."]
  }
}
```

### 429 Too Many Requests

**Problem**: Hit rate limit

**Solutions**:

1. **Wait one minute** before retrying

2. **Add IP to whitelist** in `.env`:
   ```bash
   THROTTLE_WHITELIST=127.0.0.1,localhost,YOUR_IP
   ```

3. **Clear config**:
   ```bash
   php artisan config:clear
   ```

4. **Space out requests** in scripts:
   ```bash
   # Add delays between requests
   curl http://localhost:8090/api/v1/endpoint1
   sleep 1
   curl http://localhost:8090/api/v1/endpoint2
   ```

### 500 Internal Server Error

**Problem**: Server error on API request

**Solution**:

1. **Check application logs**:
   ```bash
   # Docker
   docker-compose logs -f php

   # Native
   tail -f storage/logs/laravel.log
   ```

2. **Enable debug mode** (development only):
   ```bash
   APP_DEBUG=true
   php artisan config:clear
   ```

3. **Check Telescope** for detailed error:
   http://localhost:8090/telescope

## Database Issues

### Connection Refused

**Problem**: Cannot connect to database

**Solutions**:

1. **For Docker** - Ensure MySQL container is running:
   ```bash
   docker-compose ps mysql
   docker-compose up -d mysql
   ```

2. **For Native** - Check MySQL service:
   ```bash
   sudo systemctl status mysql
   sudo systemctl start mysql
   ```

3. **Verify credentials** in `.env` match database

4. **Check port** is correct:
   - Docker: 3306 (internal), 3307 (external)
   - Native: 3306

### Migration Errors

**Problem**: Migrations fail to run

**Solutions**:

1. **Check database connection** works

2. **Rollback and retry**:
   ```bash
   php artisan migrate:rollback
   php artisan migrate
   ```

3. **Fresh migration** (⚠️ destroys all data):
   ```bash
   php artisan migrate:fresh --seed
   ```

4. **Check for duplicate migrations**:
   ```bash
   ls database/migrations/
   ```

### Access Denied for User

**Problem**: Database user doesn't have permissions

**Solution**:

```sql
-- Grant all privileges
GRANT ALL PRIVILEGES ON primemover.* TO 'primemover'@'localhost';
FLUSH PRIVILEGES;

-- Or create user if doesn't exist
CREATE USER 'primemover'@'localhost' IDENTIFIED BY 'secret';
GRANT ALL PRIVILEGES ON primemover.* TO 'primemover'@'localhost';
FLUSH PRIVILEGES;
```

## IP Geolocation Issues

### /api/v1/ip Endpoint Fails

**Problem**: IP geolocation endpoint returns error

**Solution**:

1. **This is optional functionality**. The endpoint requires IPSTACK_API_KEY

2. **Get free API key** from https://ipstack.com

3. **Add to `.env`**:
   ```bash
   IPSTACK_API_KEY=your_api_key_here
   IPSTACK_DEFAULT_LANGUAGE=en
   ```

4. **Clear config**:
   ```bash
   php artisan config:clear
   ```

**Note**: This endpoint is not required for core functionality.

## Performance Issues

### Slow Response Times

**Solutions**:

1. **Enable caching**:
   ```bash
   CACHE_DRIVER=redis  # Instead of file
   ```

2. **Optimize autoloader**:
   ```bash
   composer dump-autoload --optimize
   ```

3. **Cache configuration** (production):
   ```bash
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   ```

4. **Check database queries** in Telescope

5. **Add database indexes** for frequently queried fields

## Getting More Help

If your issue isn't covered here:

1. **Check application logs**:
   - Docker: `docker-compose logs -f php`
   - Native: `storage/logs/laravel.log`

2. **Enable debug mode** (development only):
   ```bash
   APP_DEBUG=true
   ```

3. **Use Laravel Telescope**:
   http://localhost:8090/telescope

4. **Review other documentation**:
   - [Installation Guide](installation.md)
   - [Development Guide](development.md)
   - [API Documentation](api-documentation.md)
   - [Testing Guide](testing.md)

5. **Check Docker resources**:
   ```bash
   docker stats
   ```

6. **Verify all requirements met**:
   - PHP 8.1+
   - MySQL 8.0+
   - Required PHP extensions
   - Sufficient disk space and memory
