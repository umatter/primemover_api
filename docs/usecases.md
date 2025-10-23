# Use Cases

This document outlines common use cases for the PrimeMover API, organized by workflow type.

## Authentication & Setup

### 1. Initial Authentication
Get OAuth token for API access:
```bash
POST /api/v1/auth/token
Content-Type: application/json

{
  "grant_type": "client_credentials",
  "client_id": "your_client_id",
  "client_secret": "your_client_secret"
}
```

### 2. User Authentication
For user-based authentication:
```bash
POST /api/v1/login
Content-Type: application/json

{
  "email": "user@example.com",
  "password": "password123"
}
```

### 3. Get Current User Info
Verify authentication and get user details:
```bash
GET /api/v1/auth/user
Authorization: Bearer {token}
```

## Analytics & Monitoring

### 1. Get Experiment Overview
Retrieve all experiments for analysis:
```bash
GET /api/v1/experiments/experiments-list
Authorization: Bearer {token}
```

### 2. Monitor Crawler Status
Check different crawler states:
```bash
# Get active/protected crawlers
GET /api/v1/crawlers-protected

# Get crawlers with processed queues
GET /api/v1/crawlers-processed

# Get crawlers with unprocessed queues  
GET /api/v1/crawlers-unprocessed

# Get inactive crawlers
GET /api/v1/crawlers-inactive

# Get testing crawlers
GET /api/v1/crawlers-testing
```

### 3. Queue Status Monitoring
Monitor queue processing states:
```bash
# Get processed queues
GET /api/v1/queues-processed

# Get unprocessed queues
GET /api/v1/queues-unprocessed

# Get reviewed queues
GET /api/v1/queues-reviewed

# Get unreviewed queues
GET /api/v1/queues-unreviewed

# Get queue by job ID
GET /api/v1/queues/job/{jobid}
```

### 4. Resource Inventory
Get complete lists of resources:
```bash
# Get all agents with pagination
GET /api/v1/agents?page=1&per_page=50

# Get all configurations with search
GET /api/v1/configurations?filter[search]=config_name

# Get all proxies with filters
GET /api/v1/proxies?filter[search]=proxy_name&filter[recent]=true

# Get all jobs with filters
GET /api/v1/jobs?filter[recent]=30
```

## Experiment Management

### 1. Create New Experiment
Upload complete experiment configuration:
```bash
POST /api/v1/experiments/upload
Content-Type: application/json

{
  "json": [
    {
      "name": "Test Experiment",
      "description": "Experiment description",
      "configuration": { ... },
      "agent": { ... },
      "proxy": { ... },
      "queues": [ ... ]
    }
  ]
}
```

### 2. Download Experiment Data
Get experiment results as ZIP file:
```bash
GET /api/v1/experiments/{experiment_id}/download
Authorization: Bearer {token}
Accept: application/zip
```

### 3. Force Delete Experiment
Permanently remove experiment and all data:
```bash
DELETE /api/v1/experiments/{experiment_id}/destroy
Authorization: Bearer {token}
```

## Crawler Operations

### 1. Upload Crawler Configuration
Update crawler with new configuration:
```bash
POST /api/v1/crawlers/{crawler_id}/upload
Content-Type: multipart/form-data

# With file upload
file: @crawler_config.json

# Or with JSON content
Content-Type: application/json
{
  "content": "{\"name\":\"Updated Crawler\",\"active\":1}"
}
```

### 2. Disable Production Crawlers
Stop all production crawlers:
```bash
GET /api/v1/crawlers-disable
Authorization: Bearer {token}
```

### 3. Cleanup Crawler Data
Clean up crawler-related data:
```bash
DELETE /api/v1/cleanup
Authorization: Bearer {token}
```

### 4. Undo Crawler Operations
Revert recent crawler changes:
```bash
DELETE /api/v1/undo
Authorization: Bearer {token}
```

### 5. Force Delete Crawler
Permanently remove crawler and all associated data:
```bash
DELETE /api/v1/crawlers/{crawler_id}/destroy
Authorization: Bearer {token}
```

## Runner Integration

### 1. Update Agent Status (Runner)
Automation runner updates agent status:
```bash
PUT /api/v1/runner/agents/{agent_id}
Content-Type: application/json

{
  "status": "active",
  "last_activity": "2023-12-01T10:30:00Z",
  "current_job_id": 123,
  "error_message": null
}
```

### 2. Update Job Progress (Runner)
Runner reports job execution status:
```bash
PUT /api/v1/runner/jobs/{job_id}
Content-Type: application/json

{
  "status_code": "running",
  "status_message": "Processing page 3 of 10",
  "progress": 30,
  "started_at": "2023-12-01T10:30:00Z",
  "captcha_found": false,
  "captcha_solved": false,
  "result_data": {
    "pages_processed": 3,
    "data_collected": 45
  }
}
```

### 3. Update Queue Status (Runner)
Runner updates queue processing state:
```bash
PUT /api/v1/runner/queues/{queue_id}
Content-Type: application/json

{
  "status_code": "running",
  "processing": true,
  "processed": false,
  "progress_percentage": 75,
  "jobs_completed": 3,
  "jobs_failed": 0,
  "started_at": "2023-12-01T10:30:00Z"
}
```

## Data Management

### 1. Download Reports
Get job execution reports:
```bash
# Download report for specific job
GET /api/v1/download/report/{job_id}
Authorization: Bearer {token}

# Get report file by report ID
GET /api/v1/file/{report_id}
Authorization: Bearer {token}
```

### 2. File Storage Operations
Upload and download files via S3:
```bash
# Upload file to S3
POST /api/v1/upload
Content-Type: multipart/form-data
file: @document.pdf

# Download file from S3
GET /api/v1/download/{filename}
Authorization: Bearer {token}
```

### 3. Development Data Management
For development environments:
```bash
# Truncate development data
GET /api/v1/dev/truncate-data
Authorization: Bearer {token}

# Load test data
POST /api/v1/load-test
Content-Type: application/json
{
  "data": [...]
}
```

## Resource Management

### 1. Create Resources
Create new agents, proxies, configurations:
```bash
# Create new agent
POST /api/v1/agents
Content-Type: application/json
{
  "name": "Chrome Agent",
  "identification": "chrome",
  "location": "US-CO-COLORADO_SPRINGS",
  "active": true
}

# Create new proxy
POST /api/v1/proxies
Content-Type: application/json
{
  "name": "Proxy Server",
  "hostname": "proxy.example.com",
  "port": 8080,
  "type": "http",
  "active": 1
}

# Create new configuration
POST /api/v1/configurations
Content-Type: application/json
{
  "name": "Config Name",
  "description": "Configuration description",
  "active": 1
}
```

### 2. Update Resources
Modify existing resources:
```bash
# Update agent
PUT /api/v1/agents/{agent_id}
Content-Type: application/json
{
  "active": false,
  "description": "Updated description"
}

# Update queue
PUT /api/v1/queues/{queue_id}
Content-Type: application/json
{
  "reviewed": 1,
  "active": 0
}
```

### 3. Query Resources
Search and filter resources:
```bash
# Search agents by keyword
GET /api/v1/agents?search=chrome&page=1

# Filter configurations by crawler
GET /api/v1/configurations?filter[search]=crawler_config

# Get proxies with date filters
GET /api/v1/proxies?filter[after]=2023-01-01&filter[recent]=true
```

## Utility Operations

### 1. IP Address Checking
Check IP information:
```bash
# Check specific IP
GET /api/v1/ip/192.168.1.1

# Check current request IP
GET /api/v1/ip/
```

### 2. API Documentation
Access interactive documentation:
```bash
# View Swagger UI
GET /api/documentation

# OAuth2 callback for Swagger
GET /api/oauth2-callback
```

## Common Workflows

### 1. Complete Experiment Setup
1. Authenticate and get token
2. Create experiment with full configuration
3. Monitor queue processing status
4. Download results when complete

### 2. Crawler Monitoring & Management
1. Check crawler status across different states
2. Monitor queue processing progress
3. Update configurations as needed
4. Clean up or disable crawlers when done

### 3. Runner Operation Cycle
1. Runner fetches unprocessed queues
2. Updates agent status to "busy"
3. Processes jobs with progress updates
4. Updates queue status to "processed"
5. Sets queue as "reviewed" when complete

### 4. Data Analysis Workflow
1. Get experiment list
2. Download experiment data
3. Fetch related job and queue data
4. Download reports for analysis
5. Query resource configurations for context

## Error Handling

### Common HTTP Status Codes
- `200` - Success
- `201` - Created
- `401` - Unauthorized (check token)
- `404` - Resource not found
- `422` - Validation error (check request data)
- `429` - Rate limit exceeded (wait and retry)
- `500` - Server error (contact support)

### Rate Limiting
- Default: 1000 requests per minute per IP
- Whitelisted IPs bypass rate limiting
- Use pagination for large datasets

### Authentication Issues
- Tokens expire - refresh as needed
- Use Bearer token in Authorization header
- OAuth2 flow available for web applications
