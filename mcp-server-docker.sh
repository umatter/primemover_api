#!/bin/bash
#
# PrimeMover API MCP Server - Docker Runner
#
# This script runs the MCP server in a Docker container for use with Claude Desktop.
# It connects to the existing PrimeMover API containers via the Docker network.
#

# Change to the directory containing this script
cd "$(dirname "$0")" || exit 1

# Load environment variables from .env if it exists
if [ -f .env ]; then
    export $(grep -v '^#' .env | xargs)
fi

# Set defaults if not provided
PM_API_EMAIL=${PM_API_EMAIL:-admin@primemover.example}
PM_API_PASSWORD=${PM_API_PASSWORD:-password}
PM_API_BASE_URL=${PM_API_BASE_URL:-http://apache:80/api/v1}
COMPOSE_PROJECT_NAME=${COMPOSE_PROJECT_NAME:-primemover}

# Determine the actual network name (docker-compose prefixes it)
NETWORK_NAME="${COMPOSE_PROJECT_NAME}_primemover-network"

# Check if network exists
if ! docker network inspect "${NETWORK_NAME}" >/dev/null 2>&1; then
    echo "Error: Docker network '${NETWORK_NAME}' not found." >&2
    echo "Make sure PrimeMover API is running: docker-compose up -d" >&2
    exit 1
fi

# Build the image if it doesn't exist
if ! docker images | grep -q "${COMPOSE_PROJECT_NAME}-mcp"; then
    echo "Building MCP server image..." >&2
    docker build -t "${COMPOSE_PROJECT_NAME}-mcp" -f ./mcp_server/Dockerfile .
fi

# Run the MCP server container
# Note: --rm removes container after exit, --network connects to API
docker run --rm -i \
    --network "${NETWORK_NAME}" \
    --name "${COMPOSE_PROJECT_NAME}-mcp-$$" \
    -e PM_API_EMAIL="${PM_API_EMAIL}" \
    -e PM_API_PASSWORD="${PM_API_PASSWORD}" \
    -e PM_API_BASE_URL="${PM_API_BASE_URL}" \
    -v "$(pwd)/storage/api-docs:/app/storage/api-docs:ro" \
    -v "$(pwd)/examples:/app/examples:ro" \
    -v "$(pwd)/docs:/app/docs:ro" \
    -v "$(pwd)/README.md:/app/README.md:ro" \
    -v "$(pwd)/CLAUDE.md:/app/CLAUDE.md:ro" \
    "${COMPOSE_PROJECT_NAME}-mcp"
