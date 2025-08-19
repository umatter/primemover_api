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
      "active": 1,
      "testing": 0,
      "configuration": {
        "name": "Configuration Name",
        "description": "Configuration description",
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
      "name": "Simple Experiment {{timestamp}}",
      "description": "Basic experiment",
      "active": 1,
      "testing": 0,
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

### Scroll Job (Duration)
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

### Scroll Job (Length)
```json
{
  "name": "Scroll Distance",
  "type": "scrollbylengthjob",
  "description": "Scroll by pixel distance",
  "behaviors": [
    {
      "name": "length",
      "description": "Pixels to scroll",
      "value": 500
    },
    {
      "name": "scrollDirection",
      "description": "Scroll direction",
      "value": "DOWN"
    }
  ]
}
```

### Click Job
```json
{
  "name": "Click Button",
  "type": "clickjob",
  "description": "Click on HTML element",
  "behaviors": [
    {
      "name": "selectionType",
      "description": "Selector type",
      "value": "CSS"
    },
    {
      "name": "selector",
      "description": "Element selector",
      "value": "#submit-button"
    },
    {
      "name": "expectsPageReload",
      "description": "Whether click triggers page reload",
      "value": false
    }
  ]
}
```

### Try Click Job (Failsafe)
```json
{
  "name": "Try Click Link",
  "type": "tryclickjob",
  "description": "Click element (continue if fails)",
  "behaviors": [
    {
      "name": "selectionType",
      "description": "Selector type",
      "value": "XPATH"
    },
    {
      "name": "selector",
      "description": "Element selector",
      "value": "//a[contains(text(), 'Next')]"
    },
    {
      "name": "expectsPageReload",
      "description": "Whether click triggers page reload",
      "value": true
    }
  ]
}
```

### Switch Window/Tab Job
```json
{
  "name": "Switch to Popup",
  "type": "switchjob",
  "description": "Switch to window or frame",
  "behaviors": [
    {
      "name": "selectionType",
      "description": "Selector type",
      "value": "CSS"
    },
    {
      "name": "selector",
      "description": "Window/frame selector",
      "value": "iframe[name='popup']"
    }
  ]
}
```

### Try Switch Job (Failsafe)
```json
{
  "name": "Try Switch Frame",
  "type": "tryswitchjob",
  "description": "Switch to frame (continue if fails)",
  "behaviors": [
    {
      "name": "selectionType",
      "description": "Selector type",
      "value": "XPATH"
    },
    {
      "name": "selector",
      "description": "Frame selector",
      "value": "//iframe[@id='content-frame']"
    }
  ]
}
```

### Single Select Job
```json
{
  "name": "Select Product",
  "type": "singleselectjob",
  "description": "Select element based on criteria",
  "behaviors": [
    {
      "name": "selectionType",
      "description": "Selector type for clicking",
      "value": "CSS"
    },
    {
      "name": "selector",
      "description": "Element to click",
      "value": ".product-item"
    },
    {
      "name": "criterionExtractor",
      "description": "Decision criteria logic",
      "value": "RANDOM"
    },
    {
      "name": "decisionType",
      "description": "How to make selection",
      "value": "FIRST"
    }
  ]
}
```

### Single Select Job (Extended)
```json
{
  "name": "Select by Price",
  "type": "singleselectjob",
  "description": "Select element using separate criteria",
  "behaviors": [
    {
      "name": "clickSelectionType",
      "description": "Selector type for clicking",
      "value": "CSS"
    },
    {
      "name": "clickSelector",
      "description": "Element to click",
      "value": ".product-buy-button"
    },
    {
      "name": "criteriaSelectionType",
      "description": "Selector type for criteria",
      "value": "CSS"
    },
    {
      "name": "criteriaSelector",
      "description": "Element to evaluate",
      "value": ".product-price"
    },
    {
      "name": "criteriaExtractor",
      "description": "Extraction logic",
      "value": "TEXT_CONTENT"
    },
    {
      "name": "decisionType",
      "description": "Selection strategy",
      "value": "LOWEST_VALUE"
    }
  ]
}
```

### Handle Alert Job
```json
{
  "name": "Accept Alert",
  "type": "handlealertjob",
  "description": "Handle browser alert dialog",
  "behaviors": [
    {
      "name": "action",
      "description": "Alert action",
      "value": "ACCEPT"
    }
  ]
}
```

### Try Handle Alert Job (Failsafe)
```json
{
  "name": "Try Dismiss Alert",
  "type": "tryhandlealertjob",
  "description": "Handle alert (continue if none)",
  "behaviors": [
    {
      "name": "action",
      "description": "Alert action",
      "value": "DISMISS"
    }
  ]
}
```

### Assert Scrolled Max Job
```json
{
  "name": "Check Max Scroll",
  "type": "assertscrolledmaxjob",
  "description": "Assert page scrolled to maximum",
  "behaviors": [
    {
      "name": "direction",
      "description": "Scroll direction to check",
      "value": "DOWN"
    }
  ]
}
```

### Lambda Select Job
```json
{
  "name": "Lambda Selection",
  "type": "lambdaselectjob",
  "description": "Use AWS Lambda for selection logic",
  "behaviors": [
    {
      "name": "linkValue",
      "description": "Lambda function identifier",
      "value": "my-selection-function"
    },
    {
      "name": "linkType",
      "description": "Lambda link type",
      "value": "FUNCTION_NAME"
    },
    {
      "name": "username",
      "description": "Optional auth username",
      "value": "lambda_user"
    },
    {
      "name": "password",
      "description": "Optional auth password",
      "value": "lambda_pass"
    }
  ]
}
```

## Job Type Reference

### Navigation Jobs
- `visitjob` - Navigate to URL
- `switchjob` / `tryswitchjob` - Switch windows/tabs/frames

### Element Interaction Jobs  
- `clickjob` / `tryclickjob` - Click HTML elements
- `entertextfieldjob` / `tryentertextfieldjob` - Enter text in fields
- `singleselectjob` / `singleselecturljob` - Select elements with criteria

### Scrolling Jobs
- `scrollbydurationjob` - Scroll for time duration
- `scrollbylengthjob` - Scroll by pixel distance
- `assertscrolledmaxjob` - Assert maximum scroll reached

### Dialog Handling
- `handlealertjob` / `tryhandlealertjob` - Handle browser alerts/confirms

### Timing Jobs
- `waitjob` - Pause execution

### Advanced Jobs
- `lambdaselectjob` / `lambdasingleselectjob` - AWS Lambda integration

### Common Behavior Parameters

**Selector Parameters:**
- `selectionType` / `clickSelectionType` / `criteriaSelectionType`: CSS, XPATH, ID, CLASS_NAME
- `selector` / `clickSelector` / `criteriaSelector`: Element selector string

**Scroll Parameters:**
- `scrollDirection`: UP, DOWN, LEFT, RIGHT
- `scrollDuration` / `duration`: Time in seconds
- `length`: Pixel distance

**Decision Parameters:**
- `decisionType`: FIRST, LAST, RANDOM, LOWEST_VALUE, HIGHEST_VALUE
- `criterionExtractor` / `criteriaExtractor`: TEXT_CONTENT, ATTRIBUTE_VALUE, RANDOM

**Alert Parameters:**
- `action`: ACCEPT, DISMISS

**Text Entry Parameters:**
- `text`: Text to enter
- `appendReturn`: Whether to press Enter (true/false)
- `typingMode`: SIMULATED_NOTYPOS (default)

**Click Parameters:**
- `expectsPageReload`: Whether click triggers page reload (true/false)

## Validation Notes

1. **Boolean vs Integer:** Use `true`/`false` for boolean fields, except `configuration.active` which expects an integer
2. **Required Arrays:** `search_terms` and `media_outlet_urls` in params must be arrays, even if empty
3. **Timezone Format:** Use standard timezone names and ISO 8601 offset format
4. **Port Numbers:** Must be integers between 1-65535
5. **String Lengths:** Most name/description fields have 255 character limits
6. **Nested Structure:** Follow the exact nesting structure shown in examples
7. **Failsafe Jobs:** Jobs prefixed with `try` continue execution even if they fail
8. **Selector Types:** Use consistent selector types (CSS recommended for performance)

## Practical Upload Example

### Using the Test Experiment File

The repository includes a complete test experiment file at `examples/test-experiment.json`. Here's how to upload it:

**Step 1: Authenticate and get token**
```bash
curl -X POST 'https://pm-api.test.primemover.example/api/v1/login' \
  -H 'Content-Type: application/json' \
  -H 'Accept: application/json' \
  -d '{
    "email": "your-email@example.com",
    "password": "your-password"
  }'
```

**Step 2: Upload the test experiment**

**Important:** Make sure you're in the repository root directory where the `examples/` folder exists, or use the full path to the file.

```bash
# Method 1: Upload JSON content directly (recommended)
curl -X POST 'https://pm-api.test.primemover.example/api/v1/experiments/upload' \
  -H 'Authorization: Bearer YOUR_TOKEN_FROM_STEP_1' \
  -H 'Content-Type: application/json' \
  -H 'Accept: application/json' \
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

# Method 2: Upload from file (run from repository root directory)
curl -X POST 'https://pm-api.test.primemover.example/api/v1/experiments/upload' \
  -H 'Authorization: Bearer YOUR_TOKEN_FROM_STEP_1' \
  -H 'Content-Type: application/json' \
  -H 'Accept: application/json' \
  -d @examples/test-experiment.json

# Method 3: Upload as form data (run from repository root directory)
curl -X POST 'https://pm-api.test.primemover.example/api/v1/experiments/upload' \
  -H 'Authorization: Bearer YOUR_TOKEN_FROM_STEP_1' \
  -H 'Accept: application/json' \
  -F 'json=@examples/test-experiment.json;type=application/json'

# Method 4: Upload with full file path (adjust path as needed)
curl -X POST 'https://pm-api.test.primemover.example/api/v1/experiments/upload' \
  -H 'Authorization: Bearer YOUR_TOKEN_FROM_STEP_1' \
  -H 'Content-Type: application/json' \
  -H 'Accept: application/json' \
  -d @/full/path/to/primemover-api/examples/test-experiment.json
```

**Troubleshooting File Upload:**
- If you get "Couldn't read data from file", check that you're in the correct directory
- Use `ls examples/` to verify the file exists
- Use `pwd` to check your current directory
- Try Method 1 (inline JSON) if file path issues persist

**Step 3: Verify the upload**
```bash
curl -X GET 'https://pm-api.test.primemover.example/api/v1/experiments/experiments-list' \
  -H 'Authorization: Bearer YOUR_TOKEN' \
  -H 'Accept: application/json'
```

**Expected Success Response:**
```json
{
  "status": true,
  "message": "Experiment uploaded successfully",
  "data": {
    "experiment_id": 123,
    "crawler_id": 456,
    "created_at": "2024-01-01T10:00:00Z"
  }
}
```

### R Example for Test File Upload

```r
library(httr)
library(jsonlite)

# Step 1: Login
login_response <- POST(
  url = "https://pm-api.test.primemover.example/api/v1/login",
  add_headers(
    "Content-Type" = "application/json",
    "Accept" = "application/json"
  ),
  body = toJSON(list(
    email = "your-email@example.com",
    password = "your-password"
  ), auto_unbox = TRUE)
)

token_data <- content(login_response, "parsed")
token <- token_data$access_token

# Step 2: Upload test experiment
# Method 1: Upload from file (ensure working directory is correct)
upload_response <- POST(
  url = "https://pm-api.test.primemover.example/api/v1/experiments/upload",
  add_headers(
    "Authorization" = paste("Bearer", token),
    "Accept" = "application/json"
  ),
  body = list(json = upload_file("examples/test-experiment.json")),
  encode = "multipart"
)

# Method 2: Upload JSON content directly (more reliable)
test_experiment <- list(
  json = list(
    list(
      name = "My Test Experiment",
      description = "Testing the upload",
      configuration = list(
        name = "Basic Config",
        description = "Basic configuration",
        active = 1
      ),
      agent = list(
        name = "Chrome Test Agent",
        description = "Chrome browser for testing",
        identification = "chrome",
        multilogin_profile = list(),
        location = "US-CO-COLORADO_SPRINGS",
        active = TRUE
      ),
      queues = list(
        list(
          timezone = "America/Denver",
          timezone_utc_offset = "-06:00",
          name = "Test Queue",
          description = "Simple test queue",
          start_at = "2024-01-01T10:00:00-06:00",
          active = TRUE,
          jobs = list(
            list(
              name = "Wait 10 Seconds",
              type = "waitjob",
              description = "Wait for 10 seconds",
              behaviors = list(
                list(
                  name = "seconds",
                  description = "Duration to wait",
                  value = 10
                )
              )
            )
          )
        )
      )
    )
  )
)

upload_response <- POST(
  url = "https://pm-api.test.primemover.example/api/v1/experiments/upload",
  add_headers(
    "Authorization" = paste("Bearer", token),
    "Content-Type" = "application/json",
    "Accept" = "application/json"
  ),
  body = toJSON(test_experiment, auto_unbox = TRUE)
)

upload_result <- content(upload_response, "parsed")
print(upload_result)

# Step 3: Verify upload
experiments_response <- GET(
  url = "https://pm-api.test.primemover.example/api/v1/experiments/experiments-list",
  add_headers(
    "Authorization" = paste("Bearer", token),
    "Accept" = "application/json"
  )
)

experiments_data <- content(experiments_response, "parsed")
print(experiments_data)
```

## Response Options

Add `"response": true` to get detailed response data including created crawler information. Otherwise, you'll get a simple success status.
