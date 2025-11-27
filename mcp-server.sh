#!/bin/bash
#
# PrimeMover API MCP Server Launcher
#
# This script starts the MCP server for use with Claude Desktop and other MCP clients.
#

# Change to the directory containing this script
cd "$(dirname "$0")" || exit 1

# Check if Python 3 is installed
if ! command -v python3 &> /dev/null; then
    echo "Error: python3 is required but not installed." >&2
    exit 1
fi

# Check if dependencies are installed
if ! python3 -c "import mcp" 2>/dev/null; then
    echo "Error: MCP dependencies not installed." >&2
    echo "Please run: pip install -r requirements.txt" >&2
    exit 1
fi

# Load environment variables from .env if it exists
if [ -f .env ]; then
    export $(grep -v '^#' .env | xargs)
fi

# Check required environment variables
if [ -z "$PM_API_EMAIL" ] || [ -z "$PM_API_PASSWORD" ]; then
    echo "Error: PM_API_EMAIL and PM_API_PASSWORD must be set." >&2
    echo "Either set them in .env file or as environment variables." >&2
    exit 1
fi

# Run the MCP server
exec python3 -m mcp_server.server
