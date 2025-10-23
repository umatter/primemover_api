#!/bin/bash

# Upload Experiment Configuration Script
# Usage: ./upload_experiment.sh [experiment_id] [json_file_path]
# If experiment_id is not provided, a new experiment will be created
# If json_file_path is not provided, uses examples/test-experiment_GUI_UPLOAD.json

set -e  # Exit on any error

# Check if required environment variables are set
if [ -z "$PM_API_USERNAME" ] || [ -z "$PM_API_PASSWORD" ]; then
    echo "Error: PM_API_USERNAME and PM_API_PASSWORD environment variables must be set"
    exit 1
fi

# Default JSON file
DEFAULT_JSON_FILE="examples/test-experiment_GUI_UPLOAD.json"

# Parse command line arguments
if [ $# -eq 0 ]; then
    # No arguments provided - create new experiment with default JSON file
    EXPERIMENT_ID=""
    JSON_FILE="$DEFAULT_JSON_FILE"
elif [ $# -eq 1 ]; then
    # One argument provided - could be experiment ID or JSON file path
    if echo "$1" | grep -q '^[0-9]\+$'; then
        # Argument is numeric - treat as experiment ID, use default JSON file
        EXPERIMENT_ID="$1"
        JSON_FILE="$DEFAULT_JSON_FILE"
    else
        # Argument is not numeric - treat as JSON file path, create new experiment
        EXPERIMENT_ID=""
        JSON_FILE="$1"
    fi
elif [ $# -eq 2 ]; then
    # Two arguments provided - experiment ID and JSON file path
    EXPERIMENT_ID="$1"
    JSON_FILE="$2"
else
    echo "Usage: $0 [experiment_id] [json_file_path]"
    echo "Examples:"
    echo "  $0                                              # Creates new experiment with default JSON"
    echo "  $0 174                                          # Uses experiment 174 with default JSON"
    echo "  $0 ./experiment_config.json                     # Creates new experiment with custom JSON"
    echo "  $0 174 ./experiment_config.json                 # Uses experiment 174 with custom JSON"
    exit 1
fi

#API_BASE_URL="https://pm-api.test.primemover.example/api/v1" # deployed switch engine "test" API
API_BASE_URL="http://localhost:8090/api/v1" # local tests

# Check if JSON file exists
if [ ! -f "$JSON_FILE" ]; then
    echo "Error: JSON file '$JSON_FILE' not found"
    exit 1
fi

echo "Starting experiment upload process..."
echo "JSON File: $JSON_FILE"

# Generate current timestamp in ISO 8601 format (local time with Z suffix)
CURRENT_TIMESTAMP=$(date +"%Y-%m-%dT%H:%M:%S.%3NZ")

# Create a temporary file with updated timestamp
TEMP_JSON_FILE=$(mktemp)
trap "rm -f $TEMP_JSON_FILE" EXIT

# Update the start_at timestamp in the JSON file
sed "s/\"start_at\": \"[^\"]*\"/\"start_at\": \"$CURRENT_TIMESTAMP\"/g" "$JSON_FILE" > "$TEMP_JSON_FILE"

echo "Updated start_at timestamp to: $CURRENT_TIMESTAMP"

# Step 1: Authenticate and get JWT token
echo "Authenticating..."
AUTH_RESPONSE=$(curl -s -X POST "$API_BASE_URL/login" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -d "{
        \"email\": \"$PM_API_USERNAME\",
        \"password\": \"$PM_API_PASSWORD\"
    }")

# Extract token from response
JWT_TOKEN=$(echo "$AUTH_RESPONSE" | jq -r '.access_token // empty')

if [ -z "$JWT_TOKEN" ] || [ "$JWT_TOKEN" = "null" ]; then
    echo "Error: Failed to authenticate. Response:"
    echo "$AUTH_RESPONSE"
    exit 1
fi

echo "Authentication successful"

# Step 2: Create new experiment if no experiment ID was provided
if [ -z "$EXPERIMENT_ID" ]; then
    echo "Creating new experiment..."
    
    # Generate a timestamp-based experiment name
    TIMESTAMP=$(date +"%Y%m%d_%H%M%S")
    EXPERIMENT_NAME="Auto-Generated Experiment $TIMESTAMP"
    
    CREATE_RESPONSE=$(curl -s -X POST "$API_BASE_URL/experiments" \
        -H "Authorization: Bearer $JWT_TOKEN" \
        -H "Content-Type: application/json" \
        -H "Accept: application/json" \
        -d "{
            \"name\": \"$EXPERIMENT_NAME\",
            \"description\": \"Automatically generated experiment for configuration upload\",
            \"contact\": \"API Upload Script\",
            \"active\": 1
        }")
    
    # Extract experiment ID from response
    EXPERIMENT_ID=$(echo "$CREATE_RESPONSE" | jq -r '.data.id // .id // empty')
    
    if [ -z "$EXPERIMENT_ID" ] || [ "$EXPERIMENT_ID" = "null" ]; then
        echo "Error: Failed to create experiment. Response:"
        echo "$CREATE_RESPONSE"
        exit 1
    fi
    
    echo "New experiment created successfully with ID: $EXPERIMENT_ID"
    echo "Experiment name: $EXPERIMENT_NAME"
else
    echo "Using existing experiment ID: $EXPERIMENT_ID"
fi

# Step 3: Upload experiment configuration
echo "Uploading experiment configuration to experiment $EXPERIMENT_ID..."
UPLOAD_RESPONSE=$(curl -s -X POST "$API_BASE_URL/experiments/$EXPERIMENT_ID/upload" \
    -H "Authorization: Bearer $JWT_TOKEN" \
    -H "Accept: application/json" \
    -F "json=@$TEMP_JSON_FILE;type=application/json")

# Check if upload was successful
if echo "$UPLOAD_RESPONSE" | jq -e '.data' > /dev/null 2>&1; then
    echo "Upload successful!"
    echo "Experiment ID: $EXPERIMENT_ID"
    echo "Response:"
    echo "$UPLOAD_RESPONSE" | jq '.'
else
    echo "Upload failed. Response:"
    echo "$UPLOAD_RESPONSE" | jq '.'
    exit 1
fi

echo "Experiment upload completed successfully"
echo "Final Experiment ID: $EXPERIMENT_ID"
