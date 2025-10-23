# Routes

**Base URL:** `https://pm-api.test.primemover.example/api/v1/`

All routes require throttling middleware (1000 requests per minute per IP). When you exceed this limit, you'll receive a `429 Too Many Requests` response.

## Authentication

Most routes require authentication using Bearer tokens. Include the token in your request headers:

```
Authorization: Bearer your-token-here
Content-Type: application/json
```

### Middleware Types:
- `auth:api` - Requires valid API token
- `oauth.token` - OAuth token validation
- `formdata` - For file upload endpoints

## API Documentation Routes

| Method | Route | Controller@method | Middleware |
|--------|-------|-------------------|------------|
| GET | documentation | L5Swagger (auto-generated) | - |
| GET | oauth2-callback | L5Swagger (auto-generated) | - |

### Example: Access API Documentation
```bash
# View interactive API documentation
curl -X GET https://pm-api.test.primemover.example/api/documentation
```

## Authentication Routes

| Method | Route | Controller@method | Middleware |
|--------|-------|-------------------|------------|
| POST | login | AuthController@login | - |
| POST | forgot-password | AuthController@forgotPassword | - |
| POST | reset-password | AuthController@resetPassword | - |
| POST | auth/token | AuthController@token | oauth.token |
| GET | auth/user | AuthController@user | auth:api |

### Example: Login
```bash
curl -X POST https://pm-api.test.primemover.example/api/v1/login \
  -H "Content-Type: application/json" \
  -d '{"email": "user@example.com", "password": "password"}'
```

### Example: Get authenticated user
```bash
curl -X GET https://pm-api.test.primemover.example/api/v1/auth/user \
  -H "Authorization: Bearer your-token-here"
```

## Development Routes

| Method | Route | Controller@method | Middleware |
|--------|-------|-------------------|------------|
| GET | dev/truncate-data | Controller@truncateData | auth:api |

## Load Testing Routes

| Method | Route | Controller@method | Middleware |
|--------|-------|-------------------|------------|
| POST | load-test | CrawlerController@load | - |

## Runner Routes (for automated systems)

| Method | Route | Controller@method | Middleware |
|--------|-------|-------------------|------------|
| PUT | runner/agents/{agent} | RunnerAgentController@update | auth:api |
| PUT | runner/jobs/{job} | RunnerJobController@update | auth:api |
| PUT | runner/queues/{queue} | RunnerQueueController@update | auth:api |

## Resource Routes (CRUD operations)

All resource routes require `auth:api` middleware.

| Resource | Controller |
|----------|------------|
| configurations | ConfigurationController |
| agents | AgentController |
| proxies | ProxyController |
| jobs | JobController |
| queues | QueueController |
| crawlers | CrawlerController |
| experiments | ExperimentController |
| behaviors | BehaviorController |
| reports | ReportController |
| terms | TermController |
| outlets | OutletController |
| params | ParamController |

### Standard Resource Methods

Each resource supports these standard methods:

| Method | Route Pattern | Action | Description |
|--------|---------------|--------|-------------|
| GET | /{resource} | index | List all items (with pagination) |
| POST | /{resource} | store | Create new item |
| GET | /{resource}/{id} | show | Get specific item |
| PUT/PATCH | /{resource}/{id} | update | Update specific item |
| DELETE | /{resource}/{id} | destroy | Delete specific item |

### Example: Working with Agents
```bash
# List all agents
curl -X GET https://pm-api.test.primemover.example/api/v1/agents \
  -H "Authorization: Bearer your-token-here"

# Create new agent
curl -X POST https://pm-api.test.primemover.example/api/v1/agents \
  -H "Authorization: Bearer your-token-here" \
  -H "Content-Type: application/json" \
  -d '{"name": "Chrome Agent", "identification": "chrome"}'

# Get specific agent
curl -X GET https://pm-api.test.primemover.example/api/v1/agents/1 \
  -H "Authorization: Bearer your-token-here"

# Update agent
curl -X PUT https://pm-api.test.primemover.example/api/v1/agents/1 \
  -H "Authorization: Bearer your-token-here" \
  -H "Content-Type: application/json" \
  -d '{"name": "Updated Chrome Agent"}'

# Delete agent
curl -X DELETE https://pm-api.test.primemover.example/api/v1/agents/1 \
  -H "Authorization: Bearer your-token-here"
```

## Queue Routes

| Method | Route | Controller@method | Middleware |
|--------|-------|-------------------|------------|
| GET | queues/job/{jobid} | QueueController@job | auth:api |
| GET | queues-processed | QueueController@processed | auth:api |
| GET | queues-unprocessed | QueueController@unprocessed | auth:api |
| GET | queues-reviewed | QueueController@reviewed | auth:api |
| GET | queues-unreviewed | QueueController@unreviewed | auth:api |

## Experiment-specific Queue Routes

| Method | Route | Controller@method | Middleware |
|--------|-------|-------------------|------------|
| GET | experiments/{experiment}/queues-unreviewed | QueueController@experimentUnreviewed | auth:api |
| GET | experiments/{experiment}/queues-reviewed | QueueController@experimentReviewed | auth:api |
| GET | experiments/{experiment}/queues-processed | QueueController@experimentProcessed | auth:api |
| GET | experiments/{experiment}/queues-unprocessed | QueueController@experimentUnprocessed | auth:api |

## Experiment Routes

| Method | Route | Controller@method | Middleware |
|--------|-------|-------------------|------------|
| GET | experiments/experiments-list | ExperimentController@all | auth:api |
| DELETE | experiments/{experiment}/destroy | ExperimentController@forceDelete | auth:api |
| POST | experiments/upload | ExperimentController@upload | auth:api |
| POST | experiments/{experiment}/upload | ExperimentController@upload | auth:api, formdata |
| GET | experiments/{experiment}/download | ExperimentController@download | auth:api |

## Crawler Routes

| Method | Route | Controller@method | Middleware |
|--------|-------|-------------------|------------|
| GET | crawlers-processed | CrawlerController@withProcessedQueues | auth:api |
| GET | crawlers-reviewed | CrawlerController@withReviewedQueues | auth:api |
| GET | crawlers-unprocessed | CrawlerController@withUnprocessedQueues | auth:api |
| GET | crawlers-inactive | CrawlerController@inactive | auth:api |
| DELETE | crawlers/{crawler}/destroy | CrawlerController@forceDelete | auth:api |
| GET | crawlers-disable | CrawlerController@disableProdCrawlers | auth:api |
| GET | crawlers-testing | CrawlerController@testing | auth:api |
| GET | crawlers-protected | CrawlerController@index | auth:api |
| DELETE | undo | CrawlerController@undo | auth:api |
| DELETE | cleanup | CrawlerController@cleanup | auth:api |
| POST | crawlers/{crawler}/upload | CrawlerController@upload | auth:api |

## File Handling Routes

| Method | Route | Controller@method | Middleware |
|--------|-------|-------------------|------------|
| GET | file/{report} | ReportController@getFile | auth:api |
| POST | upload | S3Controller@store | auth:api |
| GET | download/{filename} | S3Controller@download | auth:api |
| GET | download/report/{job} | ReportController@download | auth:api |

### Example: File Upload
```bash
curl -X POST https://pm-api.test.primemover.example/api/v1/upload \
  -H "Authorization: Bearer your-token-here" \
  -F "file=@/path/to/your/file.zip"
```

### Example: Download File
```bash
curl -X GET https://pm-api.test.primemover.example/api/v1/download/filename.zip \
  -H "Authorization: Bearer your-token-here" \
  --output downloaded-file.zip
```

## Utility Routes

| Method | Route | Controller@method | Middleware |
|--------|-------|-------------------|------------|
| GET | ip/{ip?} | CrawlerController@ip | - |

### Example: Check IP
```bash
curl -X GET https://pm-api.test.primemover.example/api/v1/ip
# or check specific IP
curl -X GET https://pm-api.test.primemover.example/api/v1/ip/192.168.1.1
```

## Common Response Formats

### Success Response
```json
{
  "data": [...],
  "meta": {
    "current_page": 1,
    "total": 100,
    "per_page": 15
  }
}
```

### Error Response
```json
{
  "message": "Error description",
  "errors": {
    "field": ["Validation error message"]
  }
}
```

## Query Parameters

Many endpoints support query parameters for filtering and pagination:

### Common Query Parameters
- `page` - Page number for pagination (default: 1)
- `per_page` - Items per page (default: 15, max: 100)
- `search` - Search keyword for filtering results
- `sort` - Sort field (varies by resource)
- `order` - Sort direction (`asc` or `desc`)

### Resource-Specific Parameters
- **Agents/Proxies/Configurations**: `crawler_id` - Filter by crawler ID
- **Queues**: `job_id` - Filter by job ID
- **Behaviors**: `job_id` - Filter by job ID

### Example: Paginated Search
```bash
curl -X GET "https://pm-api.test.primemover.example/api/v1/agents?search=chrome&page=2&per_page=20" \
  -H "Authorization: Bearer your-token-here"
```

## Advanced Examples

### Working with Experiments
```bash
# Upload experiment data with JSON file
curl -X POST https://pm-api.test.primemover.example/api/v1/experiments/upload \
  -H "Authorization: Bearer your-token-here" \
  -F "json=@experiment-config.json;type=application/json"

# Download experiment results
curl -X GET https://pm-api.test.primemover.example/api/v1/experiments/123/download \
  -H "Authorization: Bearer your-token-here" \
  --output experiment-results.zip
```

### Working with Crawlers
```bash
# Get crawlers with processed queues
curl -X GET https://pm-api.test.primemover.example/api/v1/crawlers-processed \
  -H "Authorization: Bearer your-token-here"

# Upload crawler configuration
curl -X POST https://pm-api.test.primemover.example/api/v1/crawlers/456/upload \
  -H "Authorization: Bearer your-token-here" \
  -F "file=@crawler-config.json"
```

### Working with Queues
```bash
# Get queues for specific job
curl -X GET https://pm-api.test.primemover.example/api/v1/queues/job/789 \
  -H "Authorization: Bearer your-token-here"

# Get unprocessed queues for experiment
curl -X GET https://pm-api.test.primemover.example/api/v1/experiments/123/queues-unprocessed \
  -H "Authorization: Bearer your-token-here"
```

## Password Requirements

For password reset and user management:
- Minimum 8 characters
- Must contain at least one uppercase letter
- Must contain at least one digit
- Must contain at least one special character

## File Upload Requirements

### Supported File Types
- **JSON files**: `application/json` MIME type required
- **General uploads**: Various file types supported for S3 storage

### Upload Limits
- File size limits depend on server configuration
- JSON files are processed through the `formdata` middleware

## Common HTTP Status Codes

- `200 OK` - Request successful
- `201 Created` - Resource created successfully
- `204 No Content` - Request successful with no response body
- `400 Bad Request` - Invalid request data
- `401 Unauthorized` - Missing or invalid authentication
- `403 Forbidden` - Insufficient permissions
- `404 Not Found` - Resource not found
- `422 Unprocessable Entity` - Validation errors
- `429 Too Many Requests` - Rate limit exceeded
- `500 Internal Server Error` - Server error

## Rate Limiting

The API enforces a rate limit of 1000 requests per minute per IP address. When you exceed this limit:

- You'll receive a `429 Too Many Requests` response
- Response headers will include rate limit information:
  ```
  X-RateLimit-Limit: 1000
  X-RateLimit-Remaining: 0
  X-RateLimit-Reset: 1640995200
  ```

## Path Parameters

- `{agent}` - Agent ID (integer)
- `{experiment}` - Experiment ID (integer)
- `{job}` - Job ID (integer)
- `{queue}` - Queue ID (integer)
- `{crawler}` - Crawler ID (integer)
- `{report}` - Report ID (integer)
- `{filename}` - File name (string)
- `{ip?}` - IP address (optional string)
- `{jobid}` - Job ID for queue filtering (integer)

## Middleware Details

### `auth:api` - API Authentication
Uses Laravel Passport for OAuth2 authentication. Requires valid Bearer token in Authorization header.

### `oauth.token` - OAuth Token Processing
Automatically adds OAuth client credentials to requests. Used for token generation endpoints.

### `formdata` - Form Data Handler
Processes JSON files uploaded as form data. Supports `application/json` MIME type files and converts them to request data.

### `throttleIp` - IP-based Rate Limiting
- Default: 1000 requests per minute per IP
- Supports IP whitelisting via configuration
- Whitelisted IPs bypass rate limiting entirely

## Error Response Examples

### Validation Error (422)
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "email": ["The email field is required."],
    "password": ["The password must be at least 8 characters."]
  }
}
```

### Authentication Error (401)
```json
{
  "message": "Unauthenticated."
}
```

### Rate Limit Error (429)
```json
{
  "message": "Too Many Attempts."
}
```

### Not Found Error (404)
```json
{
  "message": "No query results for model [App\\Modules\\Agent\\Model\\Agent] 999"
}
```

## Development Features

### Registration Status
User registration is currently **disabled**. New users must be created by administrators.

### Load Testing
The `POST /load-test` endpoint is available for performance testing and does not require authentication.

### Data Truncation
The `GET /dev/truncate-data` endpoint clears development data and should only be used in development environments.

## API Documentation Access

Interactive API documentation is available at:
- **Swagger UI**: `GET /api/documentation`
- **OAuth2 Callback**: `GET /api/oauth2-callback`

These endpoints provide a web interface for exploring and testing the API.
