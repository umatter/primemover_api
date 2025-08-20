#!/bin/bash

# Upload test experiment data to the API
# Requires API_USERNAME and PM_API_PASSWORD environment variables

set -e  # Exit on any error

# Configuration
API_BASE_URL=${API_BASE_URL:-"https://pm-api.test.primemover.example/api/v1"}
EXPERIMENT_ID=174
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
JSON_FILE="$SCRIPT_DIR/test-experiment_CLI_UPLOAD.json"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Helper functions
error() {
    printf "${RED}Error: %s${NC}\n" "$1" >&2
    exit 1
}

success() {
    printf "${GREEN}%s${NC}\n" "$1"
}

info() {
    printf "${YELLOW}%s${NC}\n" "$1"
}

# Check environment variables
if [ -z "$PM_API_USERNAME" ]; then
    error "PM_API_USERNAME environment variable must be set"
fi

if [ -z "$PM_API_PASSWORD" ]; then
    error "PM_API_PASSWORD environment variable must be set"
fi

# Check if JSON file exists
if [ ! -f "$JSON_FILE" ]; then
    error "test-experiment.json not found at $JSON_FILE"
fi

# Check if jq is available
if ! command -v jq >/dev/null 2>&1; then
    error "jq is required but not installed. Please install jq first."
fi

info "Authenticating with API at $API_BASE_URL..."

# Authenticate and get token
AUTH_RESPONSE=$(curl -s -w "\n%{http_code}" -X POST "$API_BASE_URL/login" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -d "{\"email\":\"$PM_API_USERNAME\",\"password\":\"$PM_API_PASSWORD\"}")

# Extract HTTP status code and response body
HTTP_STATUS=$(echo "$AUTH_RESPONSE" | tail -n1)
AUTH_BODY=$(echo "$AUTH_RESPONSE" | head -n -1)

if [ "$HTTP_STATUS" != "200" ]; then
    error "Authentication failed (HTTP $HTTP_STATUS):\n$AUTH_BODY"
fi

# Extract access token
ACCESS_TOKEN=$(echo "$AUTH_BODY" | jq -r '.access_token // empty')

if [ -z "$ACCESS_TOKEN" ]; then
    error "Could not extract access token from response:\n$AUTH_BODY"
fi

success "Authentication successful!"
info "Uploading experiment data to experiment $EXPERIMENT_ID..."

# Read JSON content and escape it properly for the API
JSON_CONTENT=$(cat "$JSON_FILE" | jq -c .)

# Upload experiment data
UPLOAD_RESPONSE=$(curl -s -w "\n%{http_code}" -X POST "$API_BASE_URL/experiments/$EXPERIMENT_ID/upload" \
    -H "Authorization: Bearer $ACCESS_TOKEN" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -d "{\"content\":$(echo "$JSON_CONTENT" | jq -R .)}")

# Extract HTTP status code and response body
HTTP_STATUS=$(echo "$UPLOAD_RESPONSE" | tail -n1)
UPLOAD_BODY=$(echo "$UPLOAD_RESPONSE" | head -n -1)

if [ "$HTTP_STATUS" = "200" ]; then
    success "Upload successful!"
    info "Response:"
    echo "$UPLOAD_BODY" | jq .
else
    error "Upload failed (HTTP $HTTP_STATUS):\n$UPLOAD_BODY"
fi

success "Script completed successfully!"
