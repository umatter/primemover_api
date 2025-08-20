# CLI Upload Instructions for Experiment Data

## Overview
This document provides comprehensive instructions for implementing a CLI command that uploads experiment configuration data to an existing experiment via the API, replicating the functionality of the GUI upload feature.

## API Endpoint Analysis

### Endpoint
```
POST https://pm-api.test.primemover.example/api/v1/experiments/{experiment_id}/upload
```

### Method Details
- **HTTP Method:** POST
- **Controller:** `ExperimentController::upload()`
- **Request Handler:** `ExperimentUploadRequest`
- **Repository:** `ExperimentRepository::upload()`

## Authentication

Include Bearer token in the Authorization header:
```
Authorization: Bearer {jwt_token}
```

## Request Format

The request must be sent as `multipart/form-data` with the following specifications:

- **Content-Type:** `multipart/form-data`
- **Field name:** `json`
- **File type:** `application/json`
- **Data format:** JSON array containing crawler configuration objects

## JSON Structure Requirements

Based on the validation rules in `ExperimentUploadRequest.php`, the JSON must follow this exact structure:

```json
{
  "json": [
    {
      "name": "string (required, max 255 chars)",
      "description": "string (nullable, max 255 chars)",
      "configuration": {
        "name": "string (required, max 255 chars)",
        "description": "string (required, max 255 chars)", 
        "active": "integer (required)",
        "params": {
          "search_terms": ["array (nullable)"],
          "media_outlet_urls": ["array (nullable)"]
        },
        "preferences": ["array (nullable)"]
      },
      "proxy": {
        "name": "string (required, max 255 chars)",
        "description": "string (nullable, max 255 chars)",
        "hostname": "string (nullable, max 255 chars)",
        "port": "integer (nullable, max 65535)",
        "username": "string (required unless type=http, max 255 chars)",
        "password": "string (required unless type=http, max 255 chars)",
        "active": "boolean (required)",
        "type": "string (required)"
      },
      "agent": {
        "name": "string (required, max 255 chars)",
        "description": "string (required, max 255 chars)",
        "identification": "string (required, max 255 chars)",
        "multilogin_id": "string (nullable, max 255 chars)",
        "multilogin_profile": "object (required)",
        "location": "object (required)",
        "active": "boolean (required)"
      },
      "queues": [
        {
          "timezone": "string (required, max 255 chars)",
          "timezone_utc_offset": "string (required, max 6 chars)",
          "name": "string (required, max 255 chars)",
          "description": "string (required, max 255 chars)",
          "start_at": "string (required, max 255 chars)",
          "active": "boolean (required)",
          "jobs": [
            {
              "name": "string (required, max 255 chars)",
              "type": "string (required, max 255 chars)",
              "description": "string (required, max 255 chars)",
              "active": "boolean (optional)",
              "behaviors": [
                {
                  "name": "string (required, max 255 chars)",
                  "description": "string (nullable, max 255 chars)",
                  "active": "boolean (optional)",
                  "value": "any type (nullable)"
                }
              ]
            }
          ]
        }
      ]
    }
  ]
}
```

## Implementation Examples

### cURL Command
```bash
curl -X POST 'https://pm-api.test.primemover.example/api/v1/experiments/174/upload' \
  -H 'Authorization: Bearer YOUR_JWT_TOKEN' \
  -H 'Accept: application/json' \
  -F 'json=@experiment_config.json;type=application/json'
```

### Python Example
```python
import requests

url = "https://pm-api.test.primemover.example/api/v1/experiments/174/upload"
headers = {
    "Authorization": "Bearer YOUR_JWT_TOKEN",
    "Accept": "application/json"
}

with open('experiment_config.json', 'rb') as f:
    files = {
        'json': ('experiment_config.json', f, 'application/json')
    }
    response = requests.post(url, headers=headers, files=files)
```

### Node.js Example
```javascript
const FormData = require('form-data');
const fs = require('fs');
const axios = require('axios');

const form = new FormData();
form.append('json', fs.createReadStream('experiment_config.json'), {
    contentType: 'application/json'
});

axios.post('https://pm-api.test.primemover.example/api/v1/experiments/174/upload', form, {
    headers: {
        'Authorization': 'Bearer YOUR_JWT_TOKEN',
        'Accept': 'application/json',
        ...form.getHeaders()
    }
});
```

## Response Handling

### Success Response (200)
Returns the uploaded crawler data:
```json
{
  "data": {
    // Uploaded crawler configuration data
  }
}
```

### Validation Error (422)
Returns validation errors:
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "json.0.name": ["The json.0.name field is required."],
    "json.0.proxy.username": ["The json.0.proxy.username field is required when json.0.proxy.type is not http."]
  }
}
```

### Other Error Responses
- **401 Unauthorized:** Invalid or expired JWT token
- **404 Not Found:** Experiment with specified ID doesn't exist
- **500 Internal Server Error:** Server-side processing error

## Optional Parameters

### Response Parameter
Add `response=true` to get updated crawler data in the response:
```bash
curl -X POST 'https://pm-api.test.primemover.example/api/v1/experiments/174/upload' \
  -H 'Authorization: Bearer YOUR_JWT_TOKEN' \
  -F 'json=@config.json;type=application/json' \
  -F 'response=true'
```

When `response=true`, the API returns:
```json
{
  "data": {
    "crawlers": [
      // Array of updated crawler objects with relationships
    ]
  }
}
```

## Key Implementation Notes

1. **Array Format:** The `json` field expects an **array** of crawler objects, even when uploading a single crawler configuration.

2. **Validation:** All validation rules defined in `ExperimentUploadRequest.php` are strictly enforced.

3. **Transaction Safety:** The upload process uses database transactions. If any part of the upload fails, all changes are rolled back.

4. **Existing Experiment:** The experiment ID in the URL path must reference an existing experiment.

5. **Proxy Username/Password:** These fields are required unless the proxy type is "http".

6. **File Upload:** The JSON data must be uploaded as a file with the correct MIME type (`application/json`).

7. **Data Processing:** The system processes the uploaded data through:
   - Request validation (`ExperimentUploadRequest`)
   - Data mapping (`DataMapper`)
   - Service layer processing (`ExperimentService`)
   - Repository layer (`ExperimentRepository`)

## Error Handling Best Practices

1. **Check HTTP Status Codes:** Always verify the response status before processing the response body.

2. **Parse Validation Errors:** For 422 responses, parse the `errors` object to provide meaningful feedback.

3. **Handle Authentication:** Implement token refresh logic for 401 responses.

4. **Retry Logic:** Consider implementing retry logic for 500 errors with exponential backoff.

5. **Validate JSON Before Upload:** Pre-validate the JSON structure locally to catch obvious errors before making the API call.

## Testing

### Test with Sample Data
Use the provided sample data structure to test your CLI implementation:

```json
[
  {
    "name": "Test Crawler",
    "description": "CLI Upload Test",
    "configuration": {
      "name": "Test Configuration",
      "description": "CLI Upload Test Configuration",
      "active": 1,
      "params": {
        "search_terms": [],
        "media_outlet_urls": []
      },
      "preferences": []
    },
    "proxy": {
      "name": "Test Proxy",
      "description": "CLI Upload Test Proxy",
      "hostname": "127.0.0.1",
      "port": 8080,
      "username": "testuser",
      "password": "testpass",
      "active": true,
      "type": "http"
    },
    "agent": {
      "name": "Test Agent",
      "description": "CLI Upload Test Agent",
      "identification": "chrome",
      "multilogin_id": null,
      "multilogin_profile": {},
      "location": "US-CA-LOS_ANGELES",
      "active": true
    },
    "queues": [
      {
        "timezone": "America/Los_Angeles",
        "timezone_utc_offset": "-08:00",
        "name": "Test Queue",
        "description": "CLI Upload Test Queue",
        "start_at": "2025-01-01T12:00:00Z",
        "active": true,
        "jobs": [
          {
            "name": "Test Job",
            "type": "waitjob",
            "description": "CLI Upload Test Job",
            "active": true,
            "behaviors": [
              {
                "name": "seconds",
                "description": "Wait time",
                "active": true,
                "value": 5
              }
            ]
          }
        ]
      }
    ]
  }
]
```

This documentation should provide your development team with all the necessary information to implement a CLI command that successfully uploads experiment data to the API, matching the functionality of the GUI upload feature.
