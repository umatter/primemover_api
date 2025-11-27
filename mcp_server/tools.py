"""
MCP Tools for PrimeMover API

Defines tools that AI assistants can call to interact with the API.
"""

import json
from typing import Optional
from .api_client import PrimeMoverAPIClient


class ToolProvider:
    """Provides tools for API interactions."""

    def __init__(self, api_client: PrimeMoverAPIClient):
        """
        Initialize with API client.

        Args:
            api_client: Authenticated API client instance
        """
        self.api = api_client

    # Listing Tools

    def list_experiments(self, page: int = 1, per_page: int = 15) -> str:
        """
        List experiments (paginated).

        Args:
            page: Page number (default: 1)
            per_page: Items per page (default: 15)

        Returns:
            JSON string with paginated experiments list
        """
        try:
            result = self.api.get('experiments', params={'page': page, 'per_page': per_page})
            return json.dumps(result, indent=2)
        except Exception as e:
            return json.dumps({"error": str(e)})

    def list_all_experiments(self) -> str:
        """
        Get all experiments without pagination.

        Returns:
            JSON string with complete experiments list
        """
        try:
            result = self.api.get('experiments/experiments-list')
            return json.dumps(result, indent=2)
        except Exception as e:
            return json.dumps({"error": str(e)})

    def list_crawlers(self, status: Optional[str] = None) -> str:
        """
        List crawlers by status.

        Args:
            status: Filter by status - 'processed', 'unprocessed', 'reviewed',
                   'inactive', 'testing', or None for all

        Returns:
            JSON string with crawlers list
        """
        try:
            endpoint = 'crawlers'
            if status:
                valid_statuses = ['processed', 'unprocessed', 'reviewed', 'inactive', 'testing', 'protected']
                if status not in valid_statuses:
                    return json.dumps({
                        "error": f"Invalid status. Must be one of: {', '.join(valid_statuses)}"
                    })
                endpoint = f'crawlers-{status}'

            result = self.api.get(endpoint)
            return json.dumps(result, indent=2)
        except Exception as e:
            return json.dumps({"error": str(e)})

    def list_queues(self, status: Optional[str] = None) -> str:
        """
        List queues by status.

        Args:
            status: Filter by status - 'processed', 'unprocessed', 'reviewed',
                   'unreviewed', or None for all

        Returns:
            JSON string with queues list
        """
        try:
            endpoint = 'queues'
            if status:
                valid_statuses = ['processed', 'unprocessed', 'reviewed', 'unreviewed']
                if status not in valid_statuses:
                    return json.dumps({
                        "error": f"Invalid status. Must be one of: {', '.join(valid_statuses)}"
                    })
                endpoint = f'queues-{status}'

            result = self.api.get(endpoint)
            return json.dumps(result, indent=2)
        except Exception as e:
            return json.dumps({"error": str(e)})

    # Detail Tools

    def get_experiment(self, experiment_id: int) -> str:
        """
        Get detailed information about a specific experiment.

        Args:
            experiment_id: Experiment ID

        Returns:
            JSON string with experiment details including crawlers
        """
        try:
            result = self.api.get(f'experiments/{experiment_id}')
            return json.dumps(result, indent=2)
        except Exception as e:
            return json.dumps({"error": str(e)})

    def get_crawler(self, crawler_id: int) -> str:
        """
        Get detailed information about a specific crawler.

        Args:
            crawler_id: Crawler ID

        Returns:
            JSON string with crawler details
        """
        try:
            result = self.api.get(f'crawlers/{crawler_id}')
            return json.dumps(result, indent=2)
        except Exception as e:
            return json.dumps({"error": str(e)})

    def get_queue(self, queue_id: int) -> str:
        """
        Get detailed information about a specific queue.

        Args:
            queue_id: Queue ID

        Returns:
            JSON string with queue details including jobs
        """
        try:
            result = self.api.get(f'queues/{queue_id}')
            return json.dumps(result, indent=2)
        except Exception as e:
            return json.dumps({"error": str(e)})

    def get_job(self, job_id: int) -> str:
        """
        Get detailed information about a specific job.

        Args:
            job_id: Job ID

        Returns:
            JSON string with job details including behaviors
        """
        try:
            result = self.api.get(f'jobs/{job_id}')
            return json.dumps(result, indent=2)
        except Exception as e:
            return json.dumps({"error": str(e)})

    # Export Tools

    def download_experiment(self, experiment_id: int) -> str:
        """
        Download experiment data as ZIP archive.

        Args:
            experiment_id: Experiment ID

        Returns:
            Success message with file size or error
        """
        try:
            content = self.api.download(f'experiments/{experiment_id}/download')
            size_mb = len(content) / (1024 * 1024)
            return json.dumps({
                "success": True,
                "message": f"Experiment {experiment_id} downloaded successfully",
                "size_mb": round(size_mb, 2),
                "note": "File content is binary and not displayed. Use this for verification only."
            }, indent=2)
        except Exception as e:
            return json.dumps({"error": str(e)})

    def download_report(self, job_id: int) -> str:
        """
        Download report for a specific job.

        Args:
            job_id: Job ID

        Returns:
            Success message with file size or error
        """
        try:
            content = self.api.download(f'download/report/{job_id}')
            size_kb = len(content) / 1024
            return json.dumps({
                "success": True,
                "message": f"Report for job {job_id} downloaded successfully",
                "size_kb": round(size_kb, 2),
                "note": "File content is binary and not displayed. Use this for verification only."
            }, indent=2)
        except Exception as e:
            return json.dumps({"error": str(e)})
