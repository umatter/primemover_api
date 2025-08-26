#!/bin/bash

# Upload Experiment Configuration Script
# Usage: ./upload_experiment.sh <experiment_id> <json_file_path>

set -e  # Exit on any error

# Check if required environment variables are set
if [[ -z "$PM_API_USERNAME" || -z "$PM_API_PASSWORD" ]]; then
    echo "Error: PM_API_USERNAME and PM_API_PASSWORD environment variables must be set"
    exit 1
fi

# Check command line arguments
if [[ $# -ne 2 ]]; then
    echo "Usage: $0 <experiment_id> <json_file_path>"
    echo "Example: $0 174 ./experiment_config.json"
    exit 1
fi

EXPERIMENT_ID="$1"
JSON_FILE="$2"
API_BASE_URL="https://pm-api.test.primemover.example/api/v1"

# Check if JSON file exists
if [[ ! -f "$JSON_FILE" ]]; then
    echo "Error: JSON file '$JSON_FILE' not found"
    exit 1
fi

echo "Starting experiment upload process..."
echo "Experiment ID: $EXPERIMENT_ID"
echo "JSON File: $JSON_FILE"

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

if [[ -z "$JWT_TOKEN" || "$JWT_TOKEN" == "null" ]]; then
    echo "Error: Failed to authenticate. Response:"
    echo "$AUTH_RESPONSE"
    exit 1
fi

echo "Authentication successful"

# Step 2: Upload experiment configuration
echo "Uploading experiment configuration..."
UPLOAD_RESPONSE=$(curl -s -X POST "$API_BASE_URL/experiments/$EXPERIMENT_ID/upload" \
    -H "Authorization: Bearer $JWT_TOKEN" \
    -H "Accept: application/json" \
    -F "json=@$JSON_FILE;type=application/json")

# Check if upload was successful
if echo "$UPLOAD_RESPONSE" | jq -e '.data' > /dev/null 2>&1; then
    echo "Upload successful!"
    echo "Response:"
    echo "$UPLOAD_RESPONSE" | jq '.'
else
    echo "Upload failed. Response:"
    echo "$UPLOAD_RESPONSE" | jq '.'
    exit 1
fi

echo "Experiment upload completed successfully"
