# PrimeMover API MCP Server

A [Model Context Protocol](https://modelcontextprotocol.io) server that exposes the PrimeMover browser automation API to AI assistants like Claude, enabling them to understand and interact with the API through standardized documentation and read-only operations.

## Overview

The MCP server provides:

- **Documentation Resources**: Direct access to API documentation, examples, and guides
- **Read-Only Tools**: Safe API operations for listing and retrieving data
- **Export Tools**: Download experiment data and reports
- **Automatic Authentication**: Handles OAuth token management transparently

## Installation

### Prerequisites

- Python 3.8 or higher
- PrimeMover API running (either via Docker or native)
- Valid API credentials

### Install Dependencies

From the project root directory:

```bash
pip install -r requirements.txt
```

Or install globally:

```bash
pip install mcp requests python-dotenv
```

## Configuration

### Environment Variables

The MCP server requires the following environment variables:

```bash
PM_API_EMAIL=admin@primemover.example          # Your API user email
PM_API_PASSWORD=password              # Your API user password
PM_API_BASE_URL=http://localhost:8090/api/v1  # API base URL
```

You can set these in:

1. **`.env` file** (recommended for local development):
   ```bash
   cp .env.example .env
   # Edit .env and add the MCP configuration
   ```

2. **Environment variables** (for system-wide configuration):
   ```bash
   export PM_API_EMAIL=admin@primemover.example
   export PM_API_PASSWORD=password
   export PM_API_BASE_URL=http://localhost:8090/api/v1
   ```

3. **MCP client configuration** (see Claude Desktop section below)

## Usage

You can run the MCP server in two ways: **locally with Python** or **in a Docker container**.

### Option 1: Local Python (Development)

Best for local development and testing.

**Using the launcher script (recommended):**

```bash
./mcp-server.sh
```

**Direct Python invocation:**

```bash
python3 -m mcp_server.server
```

### Option 2: Docker (Deployment)

Best for deployment and production use. The Docker container automatically connects to your existing PrimeMover API containers.

**Using the Docker launcher script (recommended):**

```bash
./mcp-server-docker.sh
```

**Using docker-compose:**

```bash
# Build the image
docker-compose build mcp-server

# Run the container
docker-compose run --rm mcp-server
```

**Using docker run directly:**

```bash
# Build the image first
docker build -t primemover-mcp -f ./mcp_server/Dockerfile .

# Run the container
docker run --rm -i \
  --network primemover-network \
  -e PM_API_EMAIL=admin@primemover.example \
  -e PM_API_PASSWORD=password \
  -e PM_API_BASE_URL=http://apache:80/api/v1 \
  -v "$(pwd)/storage/api-docs:/app/storage/api-docs:ro" \
  -v "$(pwd)/examples:/app/examples:ro" \
  -v "$(pwd)/docs:/app/docs:ro" \
  -v "$(pwd)/README.md:/app/README.md:ro" \
  -v "$(pwd)/CLAUDE.md:/app/CLAUDE.md:ro" \
  primemover-mcp
```

The server runs in stdio mode and communicates via standard input/output.

### Claude Desktop Integration

To use the MCP server with Claude Desktop, add it to your configuration file:

**macOS**: `~/Library/Application Support/Claude/claude_desktop_config.json`
**Windows**: `%APPDATA%\Claude\claude_desktop_config.json`
**Linux**: `~/.config/Claude/claude_desktop_config.json`

#### Configuration for Local Python

```json
{
  "mcpServers": {
    "primemover": {
      "command": "/absolute/path/to/primemover_api/mcp-server.sh",
      "env": {
        "PM_API_EMAIL": "admin@primemover.example",
        "PM_API_PASSWORD": "password",
        "PM_API_BASE_URL": "http://localhost:8090/api/v1"
      }
    }
  }
}
```

#### Configuration for Docker

```json
{
  "mcpServers": {
    "primemover": {
      "command": "/absolute/path/to/primemover_api/mcp-server-docker.sh",
      "env": {
        "PM_API_EMAIL": "admin@primemover.example",
        "PM_API_PASSWORD": "password"
      }
    }
  }
}
```

**Note**: With Docker, the `PM_API_BASE_URL` defaults to `http://apache:80/api/v1` (Docker network address) and doesn't need to be specified.

**Important**: Use **absolute paths** to the launcher scripts.

After saving the configuration:
1. Restart Claude Desktop
2. Look for the 🔌 icon indicating MCP servers are connected
3. Claude can now access PrimeMover API documentation and tools

## Available Resources

Resources provide read-only access to documentation:

| URI | Description |
|-----|-------------|
| `docs://README` | Project overview and main README |
| `docs://CLAUDE` | Comprehensive developer guide |
| `docs://swagger` | Complete OpenAPI 3.0 specification |
| `docs://authentication` | OAuth and JWT authentication guide |
| `docs://routes` | API endpoints reference |
| `docs://examples` | JSON payload examples |
| `docs://troubleshooting` | Common issues and solutions |
| `example://experiment-upload` | Sample experiment upload JSON |

**Example usage in Claude:**
> "Show me the authentication documentation"
> "What does the experiment upload JSON structure look like?"

## Available Tools

Tools allow Claude to interact with the API:

### Listing Tools

- **`list_experiments(page, per_page)`** - List experiments with pagination
- **`list_all_experiments()`** - Get all experiments without pagination
- **`list_crawlers(status)`** - List crawlers by status (processed, unprocessed, reviewed, inactive, testing, protected)
- **`list_queues(status)`** - List queues by status (processed, unprocessed, reviewed, unreviewed)

### Detail Tools

- **`get_experiment(experiment_id)`** - Get detailed experiment information with crawlers
- **`get_crawler(crawler_id)`** - Get detailed crawler information
- **`get_queue(queue_id)`** - Get detailed queue information with jobs
- **`get_job(job_id)`** - Get detailed job information with behaviors

### Export Tools

- **`download_experiment(experiment_id)`** - Download experiment data as ZIP
- **`download_report(job_id)`** - Download report for a specific job

**Example usage in Claude:**
> "List all experiments"
> "Show me details for experiment ID 5"
> "Download the report for job 123"

## Architecture

```
mcp_server/
├── __init__.py       # Package initialization
├── server.py         # Main MCP server with FastMCP
├── api_client.py     # HTTP client with automatic authentication
├── resources.py      # Documentation resource provider
├── tools.py          # API operation tools
└── README.md         # This file
```

**Flow:**
1. MCP client (Claude Desktop) launches `mcp-server.sh`
2. Server initializes and authenticates with PrimeMover API
3. Claude can request resources (documentation) or call tools (API operations)
4. Server translates requests to API calls and returns responses
5. Token refresh happens automatically when needed

## Security Considerations

- **Read-Only by Design**: The MCP server only exposes GET operations and downloads
- **No Destructive Operations**: Cannot create, update, or delete data
- **Local Only**: Runs on stdio transport, no network exposure
- **Credential Isolation**: API credentials stored locally, not shared with MCP client
- **Auto Token Refresh**: Tokens are managed server-side with automatic refresh

## Troubleshooting

### Server won't start

**Error**: `PM_API_EMAIL and PM_API_PASSWORD must be set`

**Solution**: Ensure environment variables are set in `.env` file or exported in your shell.

---

**Error**: `Authentication failed`

**Solution**:
- Verify credentials are correct
- Ensure PrimeMover API is running at the configured URL
- Check API logs for authentication errors

---

**Error**: `MCP dependencies not installed`

**Solution**: Run `pip install -r requirements.txt`

### Claude Desktop doesn't connect

1. **Verify configuration path**: Ensure `claude_desktop_config.json` uses absolute paths
2. **Check server runs manually**: Test with `./mcp-server.sh` - should show no errors
3. **Restart Claude Desktop**: Configuration changes require a restart
4. **Check logs**: Look for MCP-related errors in Claude Desktop logs

### API calls fail

**Error**: `GET /experiments failed: 401 Unauthorized`

**Solution**:
- Token may have expired - restart the MCP server
- Check API credentials are valid
- Verify API is accessible from the server's network

---

**Error**: `Resource file not found`

**Solution**:
- Ensure you're running from the project root directory
- Verify documentation files exist (run `ls docs/` to check)

## Development

### Adding New Tools

To add a new tool:

1. Add the method to `ToolProvider` in `tools.py`:
   ```python
   def my_new_tool(self, param: str) -> str:
       """Tool description."""
       result = self.api.get(f'some-endpoint/{param}')
       return json.dumps(result, indent=2)
   ```

2. Register it in `server.py`:
   ```python
   @mcp.tool()
   def my_new_tool(param: str) -> str:
       """Tool description."""
       _, tool_provider = get_providers()
       return tool_provider.my_new_tool(param)
   ```

### Adding New Resources

To add a new resource:

1. Add the URI mapping in `resources.py`:
   ```python
   resource_map = {
       # ... existing mappings
       "docs://newdoc": self.root_dir / "docs" / "newdoc.md"
   }
   ```

2. Add to resource list:
   ```python
   {
       "uri": "docs://newdoc",
       "name": "New Documentation",
       "mimeType": "text/markdown",
       "description": "Description here"
   }
   ```

### Testing

Test the server manually:

```bash
# Start the server
./mcp-server.sh

# It will wait for MCP protocol messages on stdin
# Press Ctrl+C to stop
```

Test with Python:

```python
from mcp_server.api_client import PrimeMoverAPIClient
from mcp_server.tools import ToolProvider

client = PrimeMoverAPIClient()
tools = ToolProvider(client)

# Test a tool
result = tools.list_experiments()
print(result)
```

## Support

For issues related to:
- **PrimeMover API**: See main project documentation
- **MCP Protocol**: Visit https://modelcontextprotocol.io
- **Claude Desktop**: Check Claude Desktop documentation

## License

Same as PrimeMover API (MIT)
