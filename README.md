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

# 5.1. Verify configuration is loaded properly
docker-compose exec php php artisan config:clear

# 6. Test the API and authentication
curl http://localhost:8090/api/v1/ip

# Test login with default admin user
curl -X POST 'http://localhost:8090/api/v1/login' \
  -H 'Content-Type: application/json' \
  -H 'Accept: application/json' \
  -d '{"email": "admin@primemover.example", "password": "password"}'

# 7. View API documentation
# Open http://localhost:8080/api/documentation in your browser

# 8. Laravel Telescope dashboard  
# Telescope is already enabled in `.env.docker`. Rebuild the config cache:
docker-compose exec php php artisan config:clear
```

**Docker Services:**
- **Apache**: Web server (ports 8080/8443)
- **PHP 8.1**: Application runtime with FPM
- **MySQL 8.0**: Database (port 3307)
- **Redis**: Cache and queues (port 8889)
- **Memcached**: Additional caching layer

**Default Test Users:**
The database seeder creates these test users:
- **Administrator**: `admin@primemover.example` / `password`
- **Primemover PY**: `researcher@primemover.example` / `password` 
- **Primemover Runner**: `runner@primemover.example` / `password`

Note: Use the plain text password `"password"`, not the hash shown in the database.

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

# 6. Generate API documentation
php artisan l5-swagger:generate

# 7. Start development server
php artisan serve

# 8. Test the API
curl http://localhost:8000/api/v1/ip

# 9. View API documentation
# Open http://localhost:8000/api/documentation in your browser
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

## User Management

### User Registration Policy

**Public registration is disabled** for security reasons. Users must be added manually by administrators with database access.

### How to Add Users

Since the `/register` endpoint is disabled, new users must be created directly in the database. You'll need:
- Database access (MySQL credentials or Docker container access)
- User's name, email, and desired password

**Method 1: Using Laravel Tinker (Recommended)**
```bash
# Access the application container (Docker setup)
docker-compose exec php php artisan tinker

# Or for native setup
php artisan tinker

# Create a new user
User::create([
    'name' => 'John Doe',
    'email' => 'john@example.com', 
    'password' => bcrypt('SecurePassword123!')
]);
```

**Method 2: Direct Database Access**
```sql
-- Connect to MySQL database
INSERT INTO users (name, email, password, created_at, updated_at) 
VALUES (
    'John Doe',
    'john@example.com',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- bcrypt hash
    NOW(),
    NOW()
);
```

**Method 3: Using Database Seeder**
Add users to `database/seeders/DatabaseSeeder.php` and run:
```bash
php artisan db:seed
```

### User Authentication

Once users are created, they can authenticate normally:

## Testing with the Deployed API

### Step 1: Test API Connectivity
```bash
# Test if the API is accessible
curl -X GET https://pm-api.test.primemover.example/api/v1/ip
```

### Step 2: Authentication
You'll need valid credentials for the test environment. Contact your administrator for test user credentials.

```bash
# Login to get authentication token
curl -X POST https://pm-api.test.primemover.example/api/v1/login \
  -H "Content-Type: application/json" \
  -d '{"email": "your-test-email@example.com", "password": "your-test-password"}'
```

Expected response:
```json
{
  "user": {
    "id": 1,
    "name": "Test User",
    "email": "your-test-email@example.com"
  },
  "access_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9..."
}
```

### Step 3: Upload Test Experiment

**Option A: Using the Upload Script (Recommended)**

The repository includes a convenient shell script for uploading experiment configurations:

```bash
# Set your credentials (if not already set)
export PM_API_USERNAME="your-email@example.com"
export PM_API_PASSWORD="your-password"

# Upload to an existing experiment (replace 174 with your experiment ID)
./upload_experiment.sh 174 examples/test-experiment_GUI_UPLOAD.json
```

The script will:
1. Authenticate with your credentials
2. Upload the JSON configuration to the specified experiment
3. Display the upload results

**Option B: Upload JSON content directly**
```bash
curl -X POST https://pm-api.test.primemover.example/api/v1/experiments/upload \
  -H "Authorization: Bearer YOUR_TOKEN_FROM_STEP_2" \
  -H "Content-Type: application/json" \
  -d '{
    "json": [
      {
        "name": "My Test Experiment",
        "description": "Testing the upload",
        "configuration": {
          "name": "Basic Config",
          "description": "Basic configuration",
          "active": 1,
          "params": {
            "pi": -0.5,
            "psi": 0.08,
            "alpha": 0.36,
            "tau": 1,
            "kappa": 1,
            "beta": 0.003,
            "active": true,
            "search_terms": [
              { "term": "automation testing", "pi": -0.4241 }
            ],
            "media_outlet_urls": [
              { "domain": "example.com", "url": "https://www.example.com/", "pi": -0.05 }
            ]
          }
        },
        "agent": {
          "name": "Chrome Test Agent",
          "description": "Chrome browser for testing",
          "identification": "chrome",
          "multilogin_profile": {},
          "location": "US-CO-COLORADO_SPRINGS",
          "active": true
        },
        "queues": [
          {
            "timezone": "America/Denver",
            "timezone_utc_offset": "-06:00",
            "name": "Test Queue",
            "description": "Simple test queue",
            "start_at": "2024-01-01T10:00:00-06:00",
            "active": true,
            "jobs": [
              {
                "name": "Wait 10 Seconds",
                "type": "waitjob",
                "description": "Wait for 10 seconds",
                "behaviors": [
                  {
                    "name": "seconds",
                    "description": "Duration to wait",
                    "value": 10
                  }
                ]
              }
            ]
          }
        ]
      }
    ]
  }'
```

**Option C: Upload from file (ensure you're in the repository root directory)**
```bash
# First, make sure you're in the correct directory
cd /path/to/primemover-api
ls examples/test-experiment.json  # Should show the file exists

# Then upload
curl -X POST https://pm-api.test.primemover.example/api/v1/experiments/upload \
  -H "Authorization: Bearer YOUR_TOKEN_FROM_STEP_2" \
  -H "Content-Type: application/json" \
  -d @examples/test-experiment.json
```

### Step 4: Verify Upload Success
```bash
# List all experiments to see your uploaded experiment
curl -X GET https://pm-api.test.primemover.example/api/v1/experiments/experiments-list \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json"
```

## API Usage Examples

### Authentication
```bash
# Login with test environment credentials
curl -X POST https://pm-api.test.primemover.example/api/v1/login \
  -H "Content-Type: application/json" \
  -d '{"email": "your-test-email@example.com", "password": "your-test-password"}'

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

**Docker Development:**
```bash
# 1. Generate/update documentation
docker-compose exec php php artisan l5-swagger:generate

# 2. View documentation at:
# http://localhost:8080/api/documentation
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
- **Apache keeps restarting**:  
  • Ensure you started *all* services (`docker-compose up -d --build`, no service name).  
  • Inspect logs `docker-compose logs -f apache`
- **Telescope shows 404**:  
  • Ensure `TELESCOPE_ENABLED=true` is present in your `.env` (already in `.env.docker`) and run  
    `docker-compose exec php php artisan config:clear`
- **Container won't start**: Check logs with `docker-compose logs`

#### Native Setup Issues
- **Database connection errors**: Ensure MySQL is running and credentials in `.env` are correct
- **Permission denied**: Check file permissions with `chmod -R 755 storage bootstrap/cache`
- **Missing extensions**: Install required PHP extensions: `php-mysql`, `php-redis`, `php-curl`
- **Composer errors**: Run `composer install --no-dev` for production

#### Configuration Issues
- **"Config is not set or empty" error**: 
  • Ensure `THROTTLE_WHITELIST=127.0.0.1,localhost` is in your `.env` file
  • Run `docker-compose exec php php artisan config:clear`
  • Restart containers: `docker-compose down && docker-compose up -d`

#### Authentication Issues  
- **"Invalid Credentials" error**:
  • Use plain text passwords (e.g., `"password"`), not the bcrypt hashes from database
  • Verify user exists: `docker-compose exec php php artisan tinker` then `User::all()`
  • Check you're using `http://localhost:8080` not `https://`

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
