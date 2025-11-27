"""
PrimeMover API MCP Server

Main entry point for the Model Context Protocol server.
"""

import sys
from mcp.server.fastmcp import FastMCP
from .api_client import PrimeMoverAPIClient
from .resources import ResourceProvider
from .tools import ToolProvider


# Initialize FastMCP server
mcp = FastMCP("primemover-api")

# Initialize providers (lazy to avoid auth on import)
_api_client = None
_resource_provider = None
_tool_provider = None


def get_providers():
    """Get or initialize providers."""
    global _api_client, _resource_provider, _tool_provider

    if _api_client is None:
        _api_client = PrimeMoverAPIClient()
        _resource_provider = ResourceProvider()
        _tool_provider = ToolProvider(_api_client)

    return _resource_provider, _tool_provider


# Resource handlers

@mcp.resource("docs://{path}")
def read_documentation(path: str) -> str:
    """
    Read documentation resource.

    Args:
        path: Documentation path (e.g., 'README', 'CLAUDE', 'swagger')

    Returns:
        Documentation content
    """
    resource_provider, _ = get_providers()
    return resource_provider.read_resource(f"docs://{path}")


@mcp.resource("example://{name}")
def read_example(name: str) -> str:
    """
    Read example resource.

    Args:
        name: Example name (e.g., 'experiment-upload')

    Returns:
        Example content
    """
    resource_provider, _ = get_providers()
    return resource_provider.read_resource(f"example://{name}")


# Tool handlers - Listing tools

@mcp.tool()
def list_experiments(page: int = 1, per_page: int = 15) -> str:
    """
    List experiments (paginated).

    Args:
        page: Page number (default: 1)
        per_page: Items per page (default: 15)

    Returns:
        JSON string with paginated experiments list
    """
    _, tool_provider = get_providers()
    return tool_provider.list_experiments(page, per_page)


@mcp.tool()
def list_all_experiments() -> str:
    """
    Get all experiments without pagination.

    Returns:
        JSON string with complete experiments list
    """
    _, tool_provider = get_providers()
    return tool_provider.list_all_experiments()


@mcp.tool()
def list_crawlers(status: str = "") -> str:
    """
    List crawlers by status.

    Args:
        status: Filter by status - 'processed', 'unprocessed', 'reviewed',
               'inactive', 'testing', 'protected', or empty for all

    Returns:
        JSON string with crawlers list
    """
    _, tool_provider = get_providers()
    return tool_provider.list_crawlers(status if status else None)


@mcp.tool()
def list_queues(status: str = "") -> str:
    """
    List queues by status.

    Args:
        status: Filter by status - 'processed', 'unprocessed', 'reviewed',
               'unreviewed', or empty for all

    Returns:
        JSON string with queues list
    """
    _, tool_provider = get_providers()
    return tool_provider.list_queues(status if status else None)


# Tool handlers - Detail tools

@mcp.tool()
def get_experiment(experiment_id: int) -> str:
    """
    Get detailed information about a specific experiment.

    Args:
        experiment_id: Experiment ID

    Returns:
        JSON string with experiment details including crawlers
    """
    _, tool_provider = get_providers()
    return tool_provider.get_experiment(experiment_id)


@mcp.tool()
def get_crawler(crawler_id: int) -> str:
    """
    Get detailed information about a specific crawler.

    Args:
        crawler_id: Crawler ID

    Returns:
        JSON string with crawler details
    """
    _, tool_provider = get_providers()
    return tool_provider.get_crawler(crawler_id)


@mcp.tool()
def get_queue(queue_id: int) -> str:
    """
    Get detailed information about a specific queue.

    Args:
        queue_id: Queue ID

    Returns:
        JSON string with queue details including jobs
    """
    _, tool_provider = get_providers()
    return tool_provider.get_queue(queue_id)


@mcp.tool()
def get_job(job_id: int) -> str:
    """
    Get detailed information about a specific job.

    Args:
        job_id: Job ID

    Returns:
        JSON string with job details including behaviors
    """
    _, tool_provider = get_providers()
    return tool_provider.get_job(job_id)


# Tool handlers - Export tools

@mcp.tool()
def download_experiment(experiment_id: int) -> str:
    """
    Download experiment data as ZIP archive.

    Args:
        experiment_id: Experiment ID

    Returns:
        Success message with file size or error
    """
    _, tool_provider = get_providers()
    return tool_provider.download_experiment(experiment_id)


@mcp.tool()
def download_report(job_id: int) -> str:
    """
    Download report for a specific job.

    Args:
        job_id: Job ID

    Returns:
        Success message with file size or error
    """
    _, tool_provider = get_providers()
    return tool_provider.download_report(job_id)


# Main entry point
def main():
    """Run the MCP server."""
    try:
        # Run with stdio transport (default for desktop integrations)
        mcp.run(transport='stdio')
    except Exception as e:
        print(f"Error starting MCP server: {e}", file=sys.stderr)
        sys.exit(1)


if __name__ == "__main__":
    main()
