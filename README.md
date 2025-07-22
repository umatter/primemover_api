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

### Get Running in 5 Minutes
```bash
# 1. Clone the repository
git clone <repository-url> primemover-api
cd primemover-api

# 2. Copy environment file
cp .env.example .env

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

### Docker Development Commands
```bash
# View logs
docker-compose logs -f

# Access PHP container
docker-compose exec php bash

# Run artisan commands
docker-compose exec php php artisan migrate

# Stop services
docker-compose down
```

## Documentation

* [Infrastructure (Servers, Versions, Access)](docs/infrastructure.md)
* [Environment Setup (Frameworks, Commands)](docs/setup.md)
* [Authentication & Authorization (Login, Token)](docs/authentication.md)
* [Use Cases (Experiment, Bot, Configurations)](docs/usecases.md)
* [API Routes (Methods, URLs)](docs/routes.md)
* [JSON Examples (Upload Formats)](docs/examples.md)

## Common Issues

### Docker Issues
- **Port conflicts**: Change ports in `docker-compose.yml`
- **Permission errors**: Run `sudo chown -R $USER:$USER .`
- **Container won't start**: Check logs with `docker-compose logs`

### API Issues
- **401 Unauthorized**: Check your Bearer token is valid
- **422 Validation Error**: Check required fields in [examples.md](docs/examples.md)
- **429 Too Many Requests**: You've hit the rate limit (1000/min)

### Database Issues
- **Connection refused**: Ensure MySQL container is running
- **Migration errors**: Check database credentials in `.env`

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
