"""
MCP Resources for PrimeMover API

Exposes documentation and examples as MCP resources.
"""

import os
import json
from pathlib import Path
from typing import List, Dict, Any


class ResourceProvider:
    """Provides access to documentation and example files."""

    def __init__(self):
        """Initialize with paths to documentation."""
        # Get the project root (parent of mcp_server)
        self.root_dir = Path(__file__).parent.parent

    def get_resource_list(self) -> List[Dict[str, str]]:
        """
        Get list of available resources.

        Returns:
            List of resource descriptors with uri, name, and mimeType
        """
        return [
            {
                "uri": "docs://README",
                "name": "README - Project Overview",
                "mimeType": "text/markdown",
                "description": "Main README with project overview"
            },
            {
                "uri": "docs://CLAUDE",
                "name": "CLAUDE.md - Developer Guide",
                "mimeType": "text/markdown",
                "description": "Comprehensive development guide for PrimeMover API"
            },
            {
                "uri": "docs://swagger",
                "name": "OpenAPI Specification",
                "mimeType": "application/json",
                "description": "Complete OpenAPI 3.0 specification"
            },
            {
                "uri": "docs://authentication",
                "name": "Authentication Guide",
                "mimeType": "text/markdown",
                "description": "OAuth and JWT authentication documentation"
            },
            {
                "uri": "docs://routes",
                "name": "API Routes Reference",
                "mimeType": "text/markdown",
                "description": "Complete list of API endpoints"
            },
            {
                "uri": "docs://examples",
                "name": "JSON Examples",
                "mimeType": "text/markdown",
                "description": "Example JSON payloads for API operations"
            },
            {
                "uri": "docs://troubleshooting",
                "name": "Troubleshooting Guide",
                "mimeType": "text/markdown",
                "description": "Common issues and solutions"
            },
            {
                "uri": "example://experiment-upload",
                "name": "Example Experiment Upload JSON",
                "mimeType": "application/json",
                "description": "Complete example of experiment upload structure"
            }
        ]

    def read_resource(self, uri: str) -> str:
        """
        Read a resource by its URI.

        Args:
            uri: Resource URI (e.g., "docs://README")

        Returns:
            Resource content as string

        Raises:
            ValueError: If resource not found
        """
        # Map URIs to file paths
        resource_map = {
            "docs://README": self.root_dir / "README.md",
            "docs://CLAUDE": self.root_dir / "CLAUDE.md",
            "docs://swagger": self.root_dir / "storage" / "api-docs" / "api-docs.json",
            "docs://authentication": self.root_dir / "docs" / "authentication.md",
            "docs://routes": self.root_dir / "docs" / "routes.md",
            "docs://examples": self.root_dir / "docs" / "examples.md",
            "docs://troubleshooting": self.root_dir / "docs" / "troubleshooting.md",
            "example://experiment-upload": self.root_dir / "examples" / "test-experiment_GUI_UPLOAD.json"
        }

        file_path = resource_map.get(uri)
        if not file_path:
            raise ValueError(f"Unknown resource URI: {uri}")

        if not file_path.exists():
            raise ValueError(f"Resource file not found: {file_path}")

        # Read and return content
        with open(file_path, 'r', encoding='utf-8') as f:
            content = f.read()

        # For JSON files, validate and pretty-print
        if file_path.suffix == '.json':
            try:
                data = json.loads(content)
                return json.dumps(data, indent=2)
            except json.JSONDecodeError:
                return content

        return content
