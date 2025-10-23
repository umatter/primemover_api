# Installation Guide

This guide covers both Docker-based and native installation methods for the Primemover API.

## Prerequisites

Before you begin, ensure you have:
- **For Docker**: Docker and Docker Compose installed
- **For Native**: PHP 8.1+, MySQL 8.0+, Composer, Redis (optional)
- Basic knowledge of Laravel and REST APIs
- Git installed on your system

## Option 1: Docker Setup (Recommended)

Docker provides a consistent environment and is the easiest way to get started.

### Requirements

- Docker and Docker Compose installed
- Ports 8080, 8443, 3307, 8889 available
- At least 2GB RAM for all containers

### Installation Steps

```bash
# 1. Clone the repository
git clone <repository-url> primemover-api
cd primemover-api

# 2. Copy environment files
cp .env.docker .env
# This sets up both Laravel app config and Docker Compose variables

# 3. Build & start all containers
# IMPORTANT: run the command **without specifying a single service**.
# Using `docker-compose up -d --build apache` will rebuild only Apache and
# can leave the stack in a half-configured state. Always start the whole
# compose file unless you really know what you are doing.
docker-compose up -d --build

# 4. Install dependencies and setup
docker-compose exec php composer install
docker-compose exec php php artisan key:generate
docker-compose exec php php artisan jwt:secret --force
docker-compose exec php php artisan migrate --seed
docker-compose exec php php artisan passport:install --force

# 5. Generate API documentation
docker-compose exec php php artisan l5-swagger:generate

# 6. Verify configuration is loaded properly
docker-compose exec php php artisan config:clear

# 7. Test the API
curl http://localhost:8090/api/v1/ip

# Test login with default admin user
curl -X POST 'http://localhost:8090/api/v1/login' \
  -H 'Content-Type: application/json' \
  -H 'Accept: application/json' \
  -d '{"email": "admin@primemover.example", "password": "password"}'
```

### Docker Services

The Docker setup includes the following services:

- **Apache**: Web server (ports 8080/8443)
- **PHP 8.1**: Application runtime with FPM
- **MySQL 8.0**: Database (port 3307)
- **Redis**: Cache and queues (port 8889)
- **Memcached**: Additional caching layer

### Access Points

After successful installation:

- **API Endpoint**: http://localhost:8090
- **API Documentation**: http://localhost:8090/api/documentation
- **Laravel Telescope**: http://localhost:8090/telescope (if enabled)
- **MySQL**: localhost:3307 (username: `primemover`, password: `secret`)
- **Redis**: localhost:8889 (password: `secret`)

### Default Test Users

The database seeder creates these test users:

- **Administrator**: `admin@primemover.example` / `password`
- **Primemover PY**: `researcher@primemover.example` / `password`
- **Primemover Runner**: `runner@primemover.example` / `password`

**Note**: Use the plain text password `"password"`, not the hash shown in the database.

### Laravel Telescope

Telescope is a debugging assistant for Laravel applications.

```bash
# Telescope is already enabled in `.env.docker`
# Rebuild the config cache:
docker-compose exec php php artisan config:clear

# Access at: http://localhost:8090/telescope
```

## Option 2: Native Setup

For development without Docker or production deployments.

### Prerequisites

- PHP 8.1 or higher
- MySQL 8.0 or higher
- Composer
- Redis server (optional, for queues and caching)
- AWS S3 credentials (for file storage)
- IPStack API key (optional, for IP geolocation feature - get free key from https://ipstack.com)

### Installation Steps

```bash
# 1. Clone the repository
git clone <repository-url> primemover-api
cd primemover-api

# 2. Install dependencies
composer install

# 3. Setup environment
cp .env.example .env
# Edit .env file with your database credentials

# 4. Generate keys and setup
php artisan key:generate
php artisan jwt:secret --force

# 5. Setup database (ensure MySQL is running)
php artisan migrate --seed
php artisan passport:install --force

# 6. Generate API documentation
php artisan l5-swagger:generate

# 7. Start development server
php artisan serve

# 8. Test the API
curl http://localhost:8000/api/v1/ip

# 9. View API documentation
# Open http://localhost:8000/api/documentation in your browser
```

### Environment Configuration

Edit your `.env` file with the following settings:

```bash
# Application
APP_NAME="Primemover API"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

# Database (update with your credentials)
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=primemover
DB_USERNAME=primemover
DB_PASSWORD=secret

# Cache & Queues
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
QUEUE_CONNECTION=redis

# AWS S3 Storage (required for file operations)
AWS_DEFAULT_REGION=eu-central-1
AWS_BUCKET=your-bucket-name
AWS_ACCESS_KEY_ID=your_access_key
AWS_SECRET_ACCESS_KEY=your_secret_key

# API Documentation
L5_SWAGGER_GENERATE_ALWAYS=true
L5_SWAGGER_CONST_HOST=http://localhost:8000

# Rate Limiting
THROTTLE_WHITELIST=127.0.0.1,localhost

# Laravel Telescope (Debugging)
TELESCOPE_ENABLED=true

# IPStack API (optional, for IP geolocation)
IPSTACK_API_KEY=
IPSTACK_DEFAULT_LANGUAGE=en
```

### Database Setup

Create the database and user:

```sql
CREATE DATABASE primemover;
CREATE USER 'primemover'@'localhost' IDENTIFIED BY 'secret';
GRANT ALL PRIVILEGES ON primemover.* TO 'primemover'@'localhost';
FLUSH PRIVILEGES;
```

### Access Points

After successful installation:

- **API Endpoint**: http://localhost:8000
- **API Documentation**: http://localhost:8000/api/documentation
- **Laravel Telescope**: http://localhost:8000/telescope (if enabled)

## Post-Installation Verification

After completing either installation method, verify everything is working:

```bash
# 1. Check API is responding
curl http://localhost:8090/api/v1/ip  # Docker
# or
curl http://localhost:8000/api/v1/ip  # Native

# 2. Test authentication
curl -X POST 'http://localhost:8090/api/v1/login' \
  -H 'Content-Type: application/json' \
  -d '{"email": "admin@primemover.example", "password": "password"}'

# You should receive a JSON response with an access_token

# 3. View API documentation in browser
# Docker: http://localhost:8090/api/documentation
# Native: http://localhost:8000/api/documentation
```

## Next Steps

After installation:

1. **Add Users**: See [User Management Guide](user-management.md)
2. **Test the API**: See [Testing Guide](testing.md)
3. **Start Developing**: See [Development Guide](development.md)
4. **Review API Routes**: See [API Routes Documentation](routes.md)
5. **Check Examples**: See [JSON Examples](examples.md)

## Common Installation Issues

If you encounter problems, see the [Troubleshooting Guide](troubleshooting.md) for solutions to common issues.
