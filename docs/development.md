# Development Guide

This guide covers the development workflow, commands, and best practices for working with the Primemover API codebase.

## Development Workflow

### Making Changes

Follow this workflow when developing new features or fixing bugs:

1. **Create feature branch**:
   ```bash
   git checkout -b feature/your-feature-name
   ```

2. **Make changes and test locally**:
   - Write your code
   - Test manually
   - Run automated tests

3. **Run tests**:
   ```bash
   php artisan test
   ```

4. **Run linting**:
   ```bash
   composer lint
   ```

5. **Commit with clear messages**:
   ```bash
   git add .
   git commit -m "feat: add new feature description"
   ```

6. **Create pull request**:
   - Push your branch
   - Open PR against `master` branch
   - Request code review

### Code Standards

- **Follow PSR-12** coding standards
- **Run linter** before committing: `composer lint`
- **Add tests** for new features
- **Update documentation** when adding/changing features
- **Write clear commit messages** following conventional commits
- **Keep functions small** and focused
- **Comment complex logic** but prefer self-documenting code

## Docker Development Commands

All these commands assume you're using the Docker setup from the [Installation Guide](installation.md).

### Container Management

```bash
# Start all services
docker-compose up -d --build

# Stop all services
docker-compose down

# Restart services
docker-compose restart

# View running containers
docker-compose ps

# Rebuild containers from scratch
docker-compose down
docker-compose build --no-cache
docker-compose up -d
```

### Viewing Logs

```bash
# View all logs
docker-compose logs -f

# View specific service logs
docker-compose logs -f php
docker-compose logs -f apache
docker-compose logs -f mysql
docker-compose logs -f redis

# View last 100 lines
docker-compose logs --tail=100 php
```

### Accessing Containers

```bash
# Access PHP container
docker-compose exec php bash

# Access MySQL container
docker-compose exec mysql bash

# Access Redis CLI
docker-compose exec redis redis-cli -a secret
```

### Artisan Commands

```bash
# Run migrations
docker-compose exec php php artisan migrate

# Rollback migrations
docker-compose exec php php artisan migrate:rollback

# Fresh migration with seed
docker-compose exec php php artisan migrate:fresh --seed

# Run specific seeder
docker-compose exec php php artisan db:seed --class=DatabaseSeeder

# Clear caches
docker-compose exec php php artisan cache:clear
docker-compose exec php php artisan config:clear
docker-compose exec php php artisan route:clear
docker-compose exec php php artisan view:clear

# Generate API documentation
docker-compose exec php php artisan l5-swagger:generate

# Run tinker (Laravel REPL)
docker-compose exec php php artisan tinker

# Run queue worker
docker-compose exec php php artisan queue:work

# List all routes
docker-compose exec php php artisan route:list
```

### Database Operations

```bash
# Access MySQL
docker-compose exec mysql mysql -u primemover -p primemover
# Password: secret

# Backup database
docker-compose exec mysql mysqldump -u primemover -p primemover > backup.sql

# Restore database
docker-compose exec -T mysql mysql -u primemover -p primemover < backup.sql

# Check database status
docker-compose exec mysql mysql -u primemover -p -e "SHOW DATABASES;"
```

### Composer Commands

```bash
# Install dependencies
docker-compose exec php composer install

# Update dependencies
docker-compose exec php composer update

# Add new package
docker-compose exec php composer require vendor/package

# Remove package
docker-compose exec php composer remove vendor/package

# Dump autoload
docker-compose exec php composer dump-autoload

# Run linter
docker-compose exec php composer lint
```

### Testing

```bash
# Run all tests
docker-compose exec php php artisan test

# Run specific test suite
docker-compose exec php php artisan test --testsuite=Unit
docker-compose exec php php artisan test --testsuite=Feature

# Run with coverage
docker-compose exec php php artisan test --coverage

# Run specific test file
docker-compose exec php php artisan test tests/Feature/ExampleTest.php
```

## Native Development Commands

These commands are for native (non-Docker) development setups.

### Starting the Application

```bash
# Start development server
php artisan serve

# Start on specific port
php artisan serve --port=8080

# Start on specific host
php artisan serve --host=0.0.0.0
```

### Artisan Commands

```bash
# Run migrations
php artisan migrate

# Fresh migration with seed
php artisan migrate:fresh --seed

# Run seeder
php artisan db:seed

# Clear caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear

# Generate documentation
php artisan l5-swagger:generate

# Run tinker
php artisan tinker

# Queue worker
php artisan queue:work

# List routes
php artisan route:list
```

### Testing

```bash
# Run all tests
php artisan test

# Run with specific configuration
./vendor/bin/phpunit

# Run specific test suite
php artisan test --testsuite=Unit

# Run with coverage
php artisan test --coverage
./vendor/bin/phpunit --coverage-html coverage
```

### Code Quality

```bash
# Run linter (Laravel Pint)
composer lint

# Lint specific directory
./vendor/bin/pint app
./vendor/bin/pint database

# Run PHP Insights
./vendor/bin/phpinsights

# Fix code style issues
./vendor/bin/pint --dirty
```

## Development Tools

### Laravel Telescope

Telescope provides debugging and monitoring capabilities.

```bash
# Enable Telescope
# Add to .env:
TELESCOPE_ENABLED=true

# Clear config
php artisan config:clear

# Access Telescope dashboard
# http://localhost:8090/telescope (Docker)
# http://localhost:8000/telescope (Native)
```

### Laravel Tinker

Interactive REPL for Laravel:

```bash
# Start tinker
php artisan tinker

# Example commands in tinker:
User::all()
User::count()
Experiment::first()
Cache::flush()
```

### API Documentation

```bash
# Generate Swagger documentation
php artisan l5-swagger:generate

# Auto-generate on every request (development only)
# Add to .env:
L5_SWAGGER_GENERATE_ALWAYS=true
```

## Hot Reload Development

For frontend development with hot reload:

```bash
# Install NPM dependencies (if applicable)
npm install

# Start development server with hot reload
npm run dev

# Build for production
npm run build
```

## Common Development Tasks

### Adding a New API Endpoint

1. Create route in `routes/api.php`
2. Create controller method in `app/Modules/[Module]/Http/Controller/Api/v1/`
3. Add validation rules in `app/Modules/[Module]/Http/Request/`
4. Add Swagger annotations to controller
5. Regenerate API docs: `php artisan l5-swagger:generate`
6. Test the endpoint
7. Add automated tests

### Adding a New Model

1. Create migration: `php artisan make:migration create_table_name`
2. Create model in `app/Modules/[Module]/Model/`
3. Define relationships in model
4. Create repository in `app/Modules/[Module]/Repository/`
5. Run migration: `php artisan migrate`
6. Add factory (if needed)
7. Add tests

### Adding a New Service

1. Create service class in `app/Modules/[Module]/Service/`
2. Register in service provider if needed
3. Create facade in `app/Modules/[Module]/Facade/`
4. Add tests
5. Document usage

## Environment Configuration

### Development Environment

```bash
APP_ENV=local
APP_DEBUG=true
LOG_LEVEL=debug
TELESCOPE_ENABLED=true
L5_SWAGGER_GENERATE_ALWAYS=true
```

### Testing Environment

```bash
APP_ENV=testing
APP_DEBUG=true
DB_CONNECTION=sqlite
DB_DATABASE=:memory:
CACHE_DRIVER=array
QUEUE_CONNECTION=sync
```

## Git Workflow

### Branch Naming Convention

- `feature/description` - New features
- `fix/description` - Bug fixes
- `hotfix/description` - Urgent production fixes
- `refactor/description` - Code refactoring
- `docs/description` - Documentation updates

### Commit Message Format

Follow conventional commits:

```
<type>(<scope>): <subject>

<body>

<footer>
```

Types:
- `feat`: New feature
- `fix`: Bug fix
- `docs`: Documentation
- `style`: Formatting
- `refactor`: Code restructuring
- `test`: Adding tests
- `chore`: Maintenance

Examples:
```
feat(crawler): add video autoplay preference
fix(auth): resolve token expiration issue
docs(api): update swagger annotations
```

## Related Documentation

- [Installation Guide](installation.md) - Setup instructions
- [Testing Guide](testing.md) - How to test the API
- [API Documentation Guide](api-documentation.md) - Working with Swagger
- [Troubleshooting](troubleshooting.md) - Common development issues
