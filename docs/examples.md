# JSON Upload Examples

This document provides correct JSON examples for uploading data to the API endpoints. The examples are based on the actual validation rules in the codebase.

## Experiment Upload

**Endpoint:** `POST /api/v1/experiments/upload`  
**Content-Type:** `application/json`

### Required JSON Structure

The JSON must be wrapped in a `json` field containing an array of experiment objects:

```json
{
  "json": [
    {
      "name": "Experiment Name",
      "description": "Experiment description",
      "configuration": {
        "name": "Configuration Name",
        "description": "Configuration description",
        "active": 1,
        "params": {
          "search_terms": ["term1", "term2"],
          "media_outlet_urls": ["https://example.com"]
        },
        "preferences": [
          {
            "name": "location",
            "value": "US-CO-COLORADO_SPRINGS"
          }
        ]
      },
      "proxy": {
        "name": "Proxy Name",
        "description": "Proxy description",
        "hostname": "proxy.example.com",
        "port": 8080,
        "username": "proxy_user",
        "password": "proxy_pass",
        "active": true,
        "type": "HTTP"
      },
      "agent": {
        "name": "Agent Name",
        "description": "Agent description",
        "identification": "chrome",
        "multilogin_id": "ml_id_123",
        "multilogin_profile": {
          "name": "Profile Name",
          "os": "win",
          "browser": "mimic"
        },
        "location": "US-CO-COLORADO_SPRINGS",
        "active": true
      },
      "queues": [
        {
          "timezone": "America/Denver",
          "timezone_utc_offset": "-06:00",
          "name": "Queue Name",
          "description": "Queue description",
          "start_at": "2024-01-01T10:00:00-06:00",
          "active": true,
          "jobs": [
            {
              "name": "Job Name",
              "type": "waitjob",
              "description": "Job description",
              "active": true,
              "behaviors": [
                {
                  "name": "seconds",
                  "description": "Wait duration",
                  "active": true,
                  "value": 10
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

### Minimal Example

```json
{
  "json": [
    {
      "name": "Simple Experiment",
      "description": "Basic experiment",
      "configuration": {
        "name": "Basic Config",
        "description": "Basic configuration",
        "active": 1
      },
      "agent": {
        "name": "Basic Agent",
        "description": "Basic agent",
        "identification": "chrome",
        "multilogin_profile": {},
        "location": "US",
        "active": true
      },
      "queues": [
        {
          "timezone": "UTC",
          "timezone_utc_offset": "+00:00",
          "name": "Basic Queue",
          "description": "Basic queue",
          "start_at": "2024-01-01T00:00:00Z",
          "active": true,
          "jobs": [
            {
              "name": "Wait Job",
              "type": "waitjob",
              "description": "Simple wait",
              "behaviors": [
                {
                  "name": "seconds",
                  "description": "Wait time",
                  "value": 5
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

## Crawler Upload

**Endpoint:** `POST /api/v1/crawlers/{crawler}/upload`  
**Content-Type:** `application/json` or file upload

### For JSON Content

```json
{
  "json": {
    "crawler_data": "example",
    "settings": {
      "active": true,
      "testing": false
    }
  },
  "response": false
}
```

### For File Upload

Use multipart/form-data with a `json` file:

```bash
curl -X POST https://pm-api.test.primemover.example/api/v1/crawlers/1/upload \
  -H "Authorization: Bearer your-token" \
  -F "json=@crawler_config.json" \
  -F "response=true"
```

## Field Requirements

### Required Fields

**Experiment Level:**
- `name` (string, max 255)
- `configuration` (object)
- `agent` (object)
- `queues` (array)

**Configuration:**
- `name` (string, max 255)
- `description` (string, max 255)
- `active` (integer)

**Agent:**
- `name` (string, max 255)
- `description` (string, max 255)
- `identification` (string, max 255)
- `multilogin_profile` (object)
- `location` (string)
- `active` (boolean)

**Proxy (if provided):**
- `name` (string, max 255)
- `active` (boolean)
- `type` (string)
- `username` (required unless type is 'http')
- `password` (required unless type is 'http')

**Queue:**
- `timezone` (string, max 255)
- `timezone_utc_offset` (string, max 6)
- `name` (string, max 255)
- `description` (string, max 255)
- `start_at` (string, max 255)
- `active` (boolean)
- `jobs` (array)

**Job:**
- `name` (string, max 255)
- `type` (string, max 255)
- `description` (string, max 255)
- `behaviors` (array)

**Behavior:**
- `name` (string, max 255)

### Optional Fields

- `description` (most objects)
- `multilogin_id` (agent)
- `hostname`, `port`, `username`, `password` (proxy)
- `params`, `preferences` (configuration)
- `active` (job, behavior)
- `value` (behavior)

## Common Job Types

### Wait Job
```json
{
  "name": "Wait",
  "type": "waitjob",
  "description": "Wait for specified seconds",
  "behaviors": [
    {
      "name": "seconds",
      "description": "Duration to wait",
      "value": 10
    }
  ]
}
```

### Visit Job
```json
{
  "name": "Visit Website",
  "type": "visitjob",
  "description": "Navigate to URL",
  "behaviors": [
    {
      "name": "url",
      "description": "Target URL",
      "value": "https://example.com"
    }
  ]
}
```

### Text Entry Job
```json
{
  "name": "Enter Text",
  "type": "entertextfieldjob",
  "description": "Enter text in field",
  "behaviors": [
    {
      "name": "text",
      "description": "Text to enter",
      "value": "search query"
    },
    {
      "name": "selectionType",
      "description": "Selector type",
      "value": "XPATH"
    },
    {
      "name": "selector",
      "description": "Element selector",
      "value": "//input[@name='q']"
    },
    {
      "name": "appendReturn",
      "description": "Press Enter after text",
      "value": true
    }
  ]
}
```

### Scroll Job
```json
{
  "name": "Scroll Page",
  "type": "scrollbydurationjob",
  "description": "Scroll for duration",
  "behaviors": [
    {
      "name": "scrollDuration",
      "description": "Scroll duration in seconds",
      "value": 5
    },
    {
      "name": "scrollDirection",
      "description": "Scroll direction",
      "value": "DOWN"
    }
  ]
}
```

## Validation Notes

1. **Boolean vs Integer:** Use `true`/`false` for boolean fields, except `configuration.active` which expects an integer
2. **Required Arrays:** `search_terms` and `media_outlet_urls` in params must be arrays, even if empty
3. **Timezone Format:** Use standard timezone names and ISO 8601 offset format
4. **Port Numbers:** Must be integers between 1-65535
5. **String Lengths:** Most name/description fields have 255 character limits
6. **Nested Structure:** Follow the exact nesting structure shown in examples

## Response Options

Add `"response": true` to get detailed response data including created crawler information. Otherwise, you'll get a simple success status.
