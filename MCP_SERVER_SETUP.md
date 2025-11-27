# PrimeMover MCP Server - Quick Setup Guide

## What Was Built

A Model Context Protocol (MCP) server that allows AI assistants like Claude to interact with the PrimeMover API through:
- **Documentation Resources**: Direct access to OpenAPI specs, guides, and examples
- **Read-Only Tools**: Safe API operations for listing experiments, crawlers, queues, and jobs
- **Export Tools**: Download experiment data and reports

## Files Created

```
primemover_api/
├── mcp_server/
│   ├── __init__.py           # Package initialization
│   ├── server.py             # Main MCP server (FastMCP)
│   ├── api_client.py         # HTTP client with auto-auth
│   ├── resources.py          # Documentation provider
│   ├── tools.py              # API operation tools
│   ├── Dockerfile            # Docker image
│   └── README.md             # Detailed documentation
├── requirements.txt          # Python dependencies
├── mcp-server.sh             # Local Python launcher
├── mcp-server-docker.sh      # Docker launcher
├── .dockerignore             # Exclude MySQL data from builds
└── docker-compose.yml        # Added mcp-server service
```

## Quick Start

### For Claude Code (CLI)

**Option 1: Docker (Recommended)**

```bash
claude mcp add --transport stdio primemover \
  --env PM_API_EMAIL=admin@primemover.example \
  --env PM_API_PASSWORD=password \
  -- /absolute/path/to/primemover_api/mcp-server-docker.sh
```

**Option 2: Local Python**

```bash
claude mcp add --transport stdio primemover \
  --env PM_API_EMAIL=admin@primemover.example \
  --env PM_API_PASSWORD=password \
  --env PM_API_BASE_URL=http://localhost:8090/api/v1 \
  -- /absolute/path/to/primemover_api/mcp-server.sh
```

**Verify the server:**
```bash
claude mcp list
claude mcp get primemover
```

**Use within Claude Code:**
```bash
# Check MCP server status
/mcp

# Now you can ask Claude to interact with the API
"List all experiments from the PrimeMover API"
"Show me the OpenAPI spec for experiments"
```

**Managing the server:**
```bash
# List all configured servers
claude mcp list

# Get details for primemover server
claude mcp get primemover

# Remove the server
claude mcp remove primemover
```

**Scope options (where config is stored):**
- `--scope local` (default): Available only to you in current project
- `--scope project`: Shared with everyone via `.mcp.json` file
- `--scope user`: Available to you across all projects

**Example with project scope:**
```bash
claude mcp add --transport stdio primemover \
  --scope project \
  --env PM_API_EMAIL=admin@primemover.example \
  --env PM_API_PASSWORD=password \
  -- /absolute/path/to/primemover_api/mcp-server-docker.sh
```

---

### For Claude Desktop (JSON Config)

**Option 1: Docker (Recommended)

1. **Ensure API is running:**
   ```bash
   docker-compose up -d
   ```

2. **Configure credentials** in `.env`:
   ```bash
   PM_API_EMAIL=admin@primemover.example
   PM_API_PASSWORD=password
   ```

3. **Test the MCP server:**
   ```bash
   ./mcp-server-docker.sh
   # Press Ctrl+C to stop
   ```

4. **Integrate with Claude Desktop:**

   Edit `~/Library/Application Support/Claude/claude_desktop_config.json`:

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

   **Important:** Replace `/absolute/path/to/primemover_api/` with your actual path.

   For this system, the path is: `/home/umatter_team/tools/primemover_api/mcp-server-docker.sh`

5. **Restart Claude Desktop** and look for the 🔌 icon

**Option 2: Local Python (Desktop)

1. **Install dependencies:**
   ```bash
   pip install -r requirements.txt
   ```

2. **Configure `.env`** (same as above)

3. **Run:**
   ```bash
   ./mcp-server.sh
   ```

4. **Claude Desktop config:**
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

   **Important:** Replace `/absolute/path/to/primemover_api/` with your actual path.

## What Claude Can Do

Once connected, you can ask Claude:

- **"Show me the OpenAPI specification for the experiments endpoint"**
- **"List all active experiments"**
- **"Get details for experiment ID 5"**
- **"What are the available job types for automation?"**
- **"Download the report for job 123"**
- **"How do I structure a crawler upload JSON?"**

Claude will have access to:
- Full API documentation
- Example JSON structures
- Live experiment/crawler/queue data
- Download capabilities

## Testing

### Build the Docker image:
```bash
docker-compose build mcp-server
```

### Test the server:
```bash
# Docker
./mcp-server-docker.sh

# Local Python
./mcp-server.sh
```

The server will start and wait for MCP protocol messages (stdio mode). Press Ctrl+C to stop.

## Troubleshooting

**Error: Docker network not found**
- Solution: Run `docker-compose up -d` to start the API first
- The script automatically detects the network name (`primemover_primemover-network`)
- If issues persist, check: `docker network ls | grep primemover`

**Error: Authentication failed**
- Check credentials in `.env`
- Verify API is accessible at `http://localhost:8090/api/v1`

**Claude Code: "Connection closed" error**
- Ensure PrimeMover API is running: `docker-compose up -d`
- Verify script is executable: `chmod +x mcp-server*.sh`
- Check credentials in environment variables
- For Docker: Verify network exists with `docker network ls`

**Claude Desktop doesn't connect**
- Use absolute paths in config
- Restart Claude Desktop after config changes
- Check that script is executable: `chmod +x mcp-server*.sh`

**Permission denied on scripts**
- Run: `chmod +x mcp-server.sh mcp-server-docker.sh`

## Documentation

For complete documentation, see:
- **`mcp_server/README.md`** - Detailed MCP server documentation
- **`CLAUDE.md`** - Integration with development workflow
- **`storage/api-docs/api-docs.json`** - Full OpenAPI specification

## Architecture

```
Claude Desktop (MCP Client)
    ↓ stdio
mcp-server-docker.sh
    ↓ docker run
MCP Server Container (Python)
    ↓ HTTP
Apache Container (Laravel API)
    ↓ database
MySQL Container
```

The MCP server:
1. Runs in stdio mode for MCP protocol
2. Authenticates with the API using OAuth
3. Translates MCP tool calls to HTTP requests
4. Returns structured JSON responses to Claude
5. Auto-refreshes tokens when needed

## Security

- **Read-only by design**: No create/update/delete operations
- **No destructive actions**: Only GET requests and downloads
- **Local execution**: Runs on your machine, not exposed to network
- **Credential isolation**: API credentials stored locally
- **Docker isolation**: Containerized execution with minimal privileges
