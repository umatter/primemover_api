#!/bin/bash

# Clear Test Data Script
# Removes all experiment-related data while preserving users and system data

echo "╔════════════════════════════════════════════════════════════════╗"
echo "║         Primemover API - Clear Test Data Script               ║"
echo "╚════════════════════════════════════════════════════════════════╝"
echo ""

# Check if running in Docker or native
if command -v docker-compose &> /dev/null && docker-compose ps php &> /dev/null 2>&1; then
    echo "🐳 Running in Docker environment..."
    # Use -T flag to avoid TTY allocation issues
    docker-compose exec -T php php artisan data:clear-test "$@"
else
    echo "💻 Running in native environment..."
    php artisan data:clear-test "$@"
fi
