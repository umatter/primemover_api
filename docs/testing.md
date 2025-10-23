# Testing Guide

This guide covers how to test the Primemover API, both locally and with deployed environments.

## Testing Locally

After completing the [Installation Guide](installation.md), you can test your local installation.

### Quick Test - API Connectivity

```bash
# Docker setup
curl -X GET http://localhost:8090/api/v1/ip

# Native setup
curl -X GET http://localhost:8000/api/v1/ip
```

Expected response: Your IP address information (if IPSTACK_API_KEY is configured) or an error message.

### Quick Test - Authentication

```bash
# Docker setup
curl -X POST 'http://localhost:8090/api/v1/login' \
  -H 'Content-Type: application/json' \
  -H 'Accept: application/json' \
  -d '{"email": "admin@primemover.example", "password": "password"}'

# Native setup
curl -X POST 'http://localhost:8000/api/v1/login' \
  -H 'Content-Type: application/json' \
  -H 'Accept: application/json' \
  -d '{"email": "admin@primemover.example", "password": "password"}'
```

Expected response:
```json
{
  "user": {
    "id": 1,
    "name": "Administrator",
    "email": "admin@primemover.example"
  },
  "access_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9..."
}
```

## Testing with the Deployed API

This section covers testing with a deployed Primemover API instance.

### Step 1: Test API Connectivity

First, verify that the API is accessible:

```bash
# Test if the API is accessible
curl -X GET https://pm-api.test.primemover.example/api/v1/ip
```

If you receive a response (even if it's an error about missing API keys), the API is reachable.

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

**Save the access_token** - you'll need it for all subsequent API requests.

### Step 3: Upload Test Experiment

There are three methods to upload an experiment configuration.

#### Option A: Using the Upload Script (Recommended)

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

#### Option B: Upload JSON Content Directly

You can upload a complete experiment configuration inline:

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

#### Option C: Upload from File

Upload using one of the example JSON files in the repository:

```bash
# First, make sure you're in the correct directory
cd /path/to/primemover-api
ls examples/test-experiment_GUI_UPLOAD.json  # Should show the file exists

# Then upload
curl -X POST https://pm-api.test.primemover.example/api/v1/experiments/upload \
  -H "Authorization: Bearer YOUR_TOKEN_FROM_STEP_2" \
  -H "Content-Type: application/json" \
  -d @examples/test-experiment_GUI_UPLOAD.json
```

### Step 4: Verify Upload Success

List all experiments to see your uploaded experiment:

```bash
# List all experiments
curl -X GET https://pm-api.test.primemover.example/api/v1/experiments/experiments-list \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json"
```

Expected response:
```json
{
  "data": [
    {
      "id": 1,
      "name": "My Test Experiment",
      "description": "Testing the upload",
      "user_id": 1,
      "created_at": "2024-01-15T10:30:00+00:00",
      "updated_at": "2024-01-15T10:30:00+00:00"
    }
  ]
}
```

## Common API Testing Scenarios

### Create Simple Experiment

```bash
curl -X POST https://pm-api.test.primemover.example/api/v1/experiments \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"name": "My Experiment", "description": "Test experiment"}'
```

### List Crawlers

```bash
curl -X GET https://pm-api.test.primemover.example/api/v1/crawlers \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json"
```

### Get Specific Crawler

```bash
curl -X GET https://pm-api.test.primemover.example/api/v1/crawlers/1 \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json"
```

### List Queues

```bash
curl -X GET https://pm-api.test.primemover.example/api/v1/queues \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json"
```

### Filter Queues by Status

```bash
# Get processed queues
curl -X GET https://pm-api.test.primemover.example/api/v1/queues-processed \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json"

# Get unprocessed queues
curl -X GET https://pm-api.test.primemover.example/api/v1/queues-unprocessed \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json"
```

## Running PHPUnit Tests

The application includes automated tests using PHPUnit.

### Run All Tests

**Docker setup:**
```bash
docker-compose exec php php artisan test
```

**Native setup:**
```bash
php artisan test
```

### Run Specific Test Suite

```bash
# Run only unit tests
docker-compose exec php php artisan test --testsuite=Unit

# Run only feature tests
docker-compose exec php php artisan test --testsuite=Feature
```

### Run with Coverage

```bash
# Generate coverage report
docker-compose exec php php artisan test --coverage

# Generate HTML coverage report
docker-compose exec php ./vendor/bin/phpunit --coverage-html coverage
```

## Testing Best Practices

1. **Always Use Test Credentials**: Never use production credentials for testing
2. **Clean Up After Tests**: Delete test experiments and data when done
3. **Test Error Cases**: Don't just test happy paths, test error scenarios too
4. **Use Environment Variables**: Store credentials in environment variables, not in scripts
5. **Document Test Scenarios**: Keep track of test cases that should pass
6. **Automate Where Possible**: Use scripts like `upload_experiment.sh` for repetitive tasks

## Common Testing Errors

### 401 Unauthorized

**Cause**: Invalid or expired authentication token

**Solution**: Get a new token by logging in again

### 422 Validation Error

**Cause**: Missing or invalid data in request

**Solution**: Check the [JSON Examples](examples.md) for correct request format

### 429 Too Many Requests

**Cause**: Hit the rate limit (1000 requests per minute)

**Solution**:
- Wait a minute before trying again
- Add your IP to `THROTTLE_WHITELIST` in `.env`
- Space out your requests

### Connection Refused

**Cause**: API server is not running

**Solution**:
- Docker: Check containers are running with `docker-compose ps`
- Native: Ensure `php artisan serve` is running

## Next Steps

After successfully testing the API:

1. Review [API Routes Documentation](routes.md) for all available endpoints
2. Check [JSON Examples](examples.md) for more complex request formats
3. Read [Use Cases](usecases.md) for common automation scenarios
4. See [Development Guide](development.md) if you're contributing code

## Need Help?

If tests are failing, check the [Troubleshooting Guide](troubleshooting.md) for solutions to common issues.
