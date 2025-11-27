FROM python:3.11-slim

# Set working directory
WORKDIR /app

# Install dependencies
COPY requirements.txt .
RUN pip install --no-cache-dir -r requirements.txt

# Copy MCP server code
COPY mcp_server/ /app/mcp_server/

# Create directories for mounted volumes
RUN mkdir -p /app/storage/api-docs /app/examples /app/docs

# Set Python path to find the mcp_server module
ENV PYTHONPATH=/app

# Set environment defaults (can be overridden)
ENV PM_API_BASE_URL=http://apache:80/api/v1

# Run the MCP server
CMD ["python", "-m", "mcp_server.server"]
