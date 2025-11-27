"""
API Client for PrimeMover Laravel API

Handles authentication and HTTP requests to the PrimeMover API.
"""

import os
import time
from typing import Optional, Dict, Any
import requests
from dotenv import load_dotenv


class PrimeMoverAPIClient:
    """HTTP client for the PrimeMover API with automatic authentication."""

    def __init__(self):
        """Initialize the API client and authenticate."""
        load_dotenv()

        self.base_url = os.getenv('PM_API_BASE_URL', 'http://localhost:8090/api/v1')
        self.email = os.getenv('PM_API_EMAIL')
        self.password = os.getenv('PM_API_PASSWORD')

        if not self.email or not self.password:
            raise ValueError(
                "PM_API_EMAIL and PM_API_PASSWORD must be set in environment variables"
            )

        self.token: Optional[str] = None
        self.token_expires_at: float = 0
        self.session = requests.Session()

        # Authenticate on initialization
        self._authenticate()

    def _authenticate(self) -> None:
        """Authenticate with the API and store the token."""
        try:
            response = self.session.post(
                f"{self.base_url}/login",
                json={
                    "email": self.email,
                    "password": self.password
                },
                headers={"Accept": "application/json"}
            )
            response.raise_for_status()

            data = response.json()
            self.token = data.get('access_token')

            if not self.token:
                raise ValueError("No access_token in login response")

            # Set token expiration (default 1 hour if not specified)
            expires_in = data.get('expires_in', 3600)
            self.token_expires_at = time.time() + expires_in - 60  # 60s buffer

        except requests.exceptions.RequestException as e:
            raise RuntimeError(f"Authentication failed: {str(e)}")

    def _ensure_authenticated(self) -> None:
        """Ensure we have a valid token, refresh if needed."""
        if not self.token or time.time() >= self.token_expires_at:
            self._authenticate()

    def _get_headers(self) -> Dict[str, str]:
        """Get headers with authentication token."""
        self._ensure_authenticated()
        return {
            "Authorization": f"Bearer {self.token}",
            "Accept": "application/json",
            "Content-Type": "application/json"
        }

    def get(self, endpoint: str, params: Optional[Dict[str, Any]] = None) -> Dict[str, Any]:
        """
        Make a GET request to the API.

        Args:
            endpoint: API endpoint (without base URL)
            params: Optional query parameters

        Returns:
            JSON response data

        Raises:
            RuntimeError: If the request fails
        """
        try:
            url = f"{self.base_url}/{endpoint.lstrip('/')}"
            response = self.session.get(
                url,
                headers=self._get_headers(),
                params=params
            )
            response.raise_for_status()
            return response.json()
        except requests.exceptions.RequestException as e:
            raise RuntimeError(f"GET {endpoint} failed: {str(e)}")

    def post(self, endpoint: str, data: Optional[Dict[str, Any]] = None) -> Dict[str, Any]:
        """
        Make a POST request to the API.

        Args:
            endpoint: API endpoint (without base URL)
            data: Optional request body data

        Returns:
            JSON response data

        Raises:
            RuntimeError: If the request fails
        """
        try:
            url = f"{self.base_url}/{endpoint.lstrip('/')}"
            response = self.session.post(
                url,
                headers=self._get_headers(),
                json=data
            )
            response.raise_for_status()
            return response.json()
        except requests.exceptions.RequestException as e:
            raise RuntimeError(f"POST {endpoint} failed: {str(e)}")

    def download(self, endpoint: str) -> bytes:
        """
        Download a file from the API.

        Args:
            endpoint: API endpoint (without base URL)

        Returns:
            File content as bytes

        Raises:
            RuntimeError: If the download fails
        """
        try:
            url = f"{self.base_url}/{endpoint.lstrip('/')}"
            response = self.session.get(
                url,
                headers=self._get_headers(),
                stream=True
            )
            response.raise_for_status()
            return response.content
        except requests.exceptions.RequestException as e:
            raise RuntimeError(f"Download {endpoint} failed: {str(e)}")
