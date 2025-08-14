# Primemover API

Primemover is a web automation platform that allows users to create and manage browser automation experiments. It provides APIs for configuring crawlers, managing browser agents, and executing automated web interactions.

## What does it do?

- **Experiment Management**: Create and configure web automation experiments
- **Browser Automation**: Control browser behavior through agents and proxies  
- **Queue Management**: Schedule and monitor automation tasks
- **Data Collection**: Gather and store results from web interactions

## Quick Start

### Prerequisites
- PHP 8.1+
- Docker & Docker Compose (recommended)
- Basic knowledge of Laravel and REST APIs

### Setup Options

Choose your preferred setup method:

#### Option 1: Docker Setup (Recommended)
```bash
# 1. Clone the repository
git clone <repository-url> primemover-api
cd primemover-api

# 2. Copy environment files
cp .env.docker .env
# This sets up both Laravel app config and Docker Compose variables

# 3. Start with Docker
docker-compose up -d

# 4. Install dependencies and setup
docker-compose exec php composer install
docker-compose exec php php artisan key:generate
docker-compose exec php php artisan jwt:secret --force
docker-compose exec php php artisan migrate --seed
docker-compose exec php php artisan passport:install --force

# 5. Test the API
curl http://localhost:8080/api/v1/ip
```

**Docker Services:**
- **Apache**: Web server (ports 8080/8443)
- **PHP 8.1**: Application runtime with FPM
- **MySQL 8.0**: Database (port 3307)
- **Redis**: Cache and queues (port 8889)
- **Memcached**: Additional caching layer

#### Option 2: Native Setup
**Prerequisites:** PHP 8.1+, MySQL 8.0+, Composer, Redis (optional)

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

# 6. Start development server
php artisan serve

# 7. Test the API
curl http://localhost:8000/api/v1/ip
```

**Native Setup Requirements:**
- MySQL server running on localhost:3306
- Database named `primemover` with user `primemover`/password `secret`
- Redis server (optional, for queues and caching)
- AWS S3 credentials (for file storage)

**Docker Setup Requirements:**
- Docker and Docker Compose installed
- Ports 8080, 8443, 3307, 8889 available
- At least 2GB RAM for all containers

For detailed setup instructions, see the [Setup Guide](docs/setup.md).

## Architecture Overview

```
Frontend (Angular) ↔ API (Laravel) ↔ Database (MySQL)
                                   ↔ Queue (Redis)
                                   ↔ Storage (S3)
```

### Key Components
- **Experiments**: Top-level containers for automation workflows
- **Crawlers**: Individual automation instances  
- **Agents**: Browser configuration and identity management
- **Queues**: Task scheduling and execution
- **Jobs**: Individual automation actions (visit, click, scroll, etc.)
- **Behaviors**: Specific parameters for job execution

## Technology Stack

**Backend:**
- Laravel 10 (PHP 8.1+)
- MySQL 8.0
- Redis (queues/cache)
- Laravel Passport (OAuth)

**Infrastructure:**
- Docker & Docker Compose
- AWS S3 (file storage)
- Apache/Nginx

**Frontend:**
- Angular (separate repository)

## API Usage Examples

### Authentication
```bash
# Login
curl -X POST https://pm-api.test.primemover.example/api/v1/login \
  -H "Content-Type: application/json" \
  -d '{"email": "user@example.com", "password": "password"}'

# Use token in subsequent requests
curl -H "Authorization: Bearer YOUR_TOKEN" \
  https://pm-api.test.primemover.example/api/v1/experiments
```

### Create Simple Experiment
```bash
curl -X POST https://pm-api.test.primemover.example/api/v1/experiments \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"name": "My Experiment", "description": "Test experiment"}'
```

For complete JSON examples, see [examples.md](docs/examples.md).

## Development Workflow

### Making Changes
1. Create feature branch: `git checkout -b feature/your-feature`
2. Make changes and test locally
3. Run tests: `php artisan test`
4. Run linting: `composer lint`
5. Commit with clear messages
6. Create pull request

### Code Standards
- Follow PSR-12 coding standards
- Run `composer lint` before committing
- Add tests for new features
- Update documentation when needed

### Development Commands

#### Docker Commands
```bash
# View logs
docker-compose logs -f

# Access PHP container
docker-compose exec php bash

# Run artisan commands
docker-compose exec php php artisan migrate

# Access MySQL
docker-compose exec mysql mysql -u primemover -p primemover

# Access Redis
docker-compose exec redis redis-cli -a secret

# Stop services
docker-compose down

# Rebuild containers
docker-compose up -d --build
```

#### Native Development Commands
```bash
# Start development server
php artisan serve

# Run migrations
php artisan migrate

# Generate Swagger documentation
php artisan l5-swagger:generate

# Run tests
php artisan test

# Clear caches
php artisan cache:clear
php artisan config:clear
```

## API Documentation

### Viewing Swagger Documentation

The API documentation is automatically generated using Swagger/OpenAPI and can be viewed in several ways:

**Local Development:**
```bash
# 1. Start your Laravel server
php artisan serve

# 2. Generate/update documentation
php artisan l5-swagger:generate

# 3. View documentation at:
# http://localhost:8000/api/documentation
```

**Auto-regeneration during development:**
Add this to your `.env` file to automatically regenerate docs on each request:
```
L5_SWAGGER_GENERATE_ALWAYS=true
```

**Production Documentation:**
- Main UI: `https://your-domain/api/documentation`
- Raw JSON: `https://your-domain/docs`

**Alternative Viewers:**
- [Swagger Editor](https://editor.swagger.io/) - Copy/paste JSON from `storage/api-docs/`
- [Redoc](https://redocly.github.io/redoc/) - Upload JSON for clean documentation view
- Postman - Import JSON to create API collection

### Updating Documentation
Documentation is generated from PHPDoc annotations in your controllers. After making changes:
```bash
php artisan l5-swagger:generate
```

## Documentation

* [Infrastructure (Servers, Versions, Access)](docs/infrastructure.md)
* [Environment Setup (Frameworks, Commands)](docs/setup.md)
* [Authentication & Authorization (Login, Token)](docs/authentication.md)
* [Use Cases (Experiment, Bot, Configurations)](docs/usecases.md)
* [API Routes (Methods, URLs)](docs/routes.md)
* [JSON Examples (Upload Formats)](docs/examples.md)

## Common Issues

#### Docker Issues
- **Port conflicts**: Change ports in `docker-compose.yml`
- **Permission errors**: Run `sudo chown -R $USER:$USER .`
- **Container won't start**: Check logs with `docker-compose logs`

#### Native Setup Issues
- **Database connection errors**: Ensure MySQL is running and credentials in `.env` are correct
- **Permission denied**: Check file permissions with `chmod -R 755 storage bootstrap/cache`
- **Missing extensions**: Install required PHP extensions: `php-mysql`, `php-redis`, `php-curl`
- **Composer errors**: Run `composer install --no-dev` for production

#### API Issues
- **401 Unauthorized**: Check your Bearer token is valid
- **422 Validation Error**: Check required fields in [examples.md](docs/examples.md)
- **429 Too Many Requests**: You've hit the rate limit (1000/min)

#### Database Issues
- **Connection refused**: Ensure MySQL server is running (Docker or native)
- **Migration errors**: Check database credentials in `.env`
- **Access denied**: Verify database user has proper permissions

### Need Help?
- Check the [Setup Guide](docs/setup.md) for installation issues
- Review [API Documentation](docs/routes.md) for endpoint details
- Look at [JSON Examples](docs/examples.md) for request formats
- Check application logs: `docker-compose logs php`

## Contributing

1. Fork the repository
2. Create your feature branch
3. Make your changes
4. Add tests if applicable
5. Ensure all tests pass
6. Submit a pull request

## License

This project is licensed under the MIT License.
