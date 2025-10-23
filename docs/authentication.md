# Authentication Guide

This guide covers authentication mechanisms used by the Primemover API, including OAuth 2.0 (Laravel Passport) and JWT tokens.

## Overview

The Primemover API uses two authentication mechanisms:

1. **Laravel Passport (OAuth 2.0)** - For OAuth-based authentication
2. **JWT (JSON Web Tokens)** - For token-based API authentication

Both mechanisms work together to provide secure access to the API.

## Quick Start

### Basic Authentication Flow

1. **Login** with email and password
2. **Receive** an access token
3. **Include** the token in subsequent requests
4. **Token expires** after 24 hours (default)
5. **Re-authenticate** when needed

### Example Login Request

```bash
# Send login request
curl -X POST 'http://localhost:8090/api/v1/login' \
  -H 'Content-Type: application/json' \
  -H 'Accept: application/json' \
  -d '{
    "email": "admin@primemover.example",
    "password": "password"
  }'
```

### Example Response

```json
{
  "user": {
    "id": 1,
    "name": "Administrator",
    "email": "admin@primemover.example",
    "email_verified_at": "2024-01-15T10:30:00.000000Z",
    "created_at": "2024-01-15T10:30:00.000000Z",
    "updated_at": "2024-01-15T10:30:00.000000Z",
    "photo_url": "https://www.gravatar.com/avatar/..."
  },
  "access_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9..."
}
```

### Using the Access Token

Include the token in the Authorization header:

```bash
curl -X GET 'http://localhost:8090/api/v1/experiments' \
  -H 'Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9...' \
  -H 'Accept: application/json'
```

## Laravel Passport (OAuth 2.0)

### What is Laravel Passport?

Laravel Passport provides a full OAuth 2.0 server implementation for Laravel applications. It handles:

- Token generation and validation
- Token refresh capabilities
- API authentication
- Client credentials
- Personal access tokens

### Installation and Setup

Passport is pre-installed in the Primemover API. If setting up from scratch:

```bash
# Install Passport
composer require laravel/passport

# Run migrations
php artisan migrate

# Install Passport
php artisan passport:install --force
```

### Passport Configuration

Configuration is located in `config/auth.php`:

```php
'guards' => [
    'api' => [
        'driver' => 'passport',
        'provider' => 'users',
    ],
],
```

### OAuth Endpoints

Passport provides several OAuth endpoints:

- **Authorization**: `/oauth/authorize`
- **Token**: `/oauth/token`
- **Token Refresh**: `/oauth/token/refresh`
- **Token Revoke**: `/oauth/token/revoke`

## JWT Tokens

### What is JWT?

JWT (JSON Web Tokens) are a compact, URL-safe means of representing claims to be transferred between two parties. The token is digitally signed and can be verified and trusted.

### JWT Structure

A JWT consists of three parts separated by dots:

```
header.payload.signature
```

Example:
```
eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9.eyJhdWQiOiI1IiwianRpIjoiYWJjMTIzIiwiaWF0IjoxNjE2MTIzNDU2fQ.signature_here
```

**Header:**
```json
{
  "typ": "JWT",
  "alg": "RS256"
}
```

**Payload:**
```json
{
  "aud": "5",
  "jti": "abc123",
  "iat": 1616123456,
  "nbf": 1616123456,
  "exp": 1616209856,
  "sub": "1",
  "scopes": []
}
```

### JWT Configuration

JWT settings are configured in `.env`:

```bash
# Token lifetime in minutes (default: 1440 = 24 hours)
JWT_TTL=1440

# JWT Secret (generated automatically)
JWT_SECRET=your_generated_secret_here
```

Generate JWT secret:

```bash
php artisan jwt:secret --force
```

### Token Expiration

- **Default TTL**: 1440 minutes (24 hours)
- **Refresh**: Not automatic - user must re-authenticate
- **Validation**: Token is validated on each request

### Token Claims

The JWT payload includes:

- `sub`: User ID (subject)
- `iat`: Issued at timestamp
- `exp`: Expiration timestamp
- `nbf`: Not before timestamp
- `jti`: JWT ID (unique identifier)
- `aud`: Audience
- `scopes`: OAuth scopes (if applicable)

## API Authentication

### Protected Routes

Most API routes require authentication:

```php
// In routes/api.php
Route::middleware('auth:api')->group(function () {
    Route::apiResource('experiments', ExperimentController::class);
    Route::apiResource('crawlers', CrawlerController::class);
    // ... other protected routes
});
```

### Public Routes

These routes do not require authentication:

- `POST /api/v1/login` - Login endpoint
- `GET /api/v1/ip` - IP information (if configured)
- `GET /api/documentation` - API documentation

## Rate Limiting

The API implements rate limiting to prevent abuse.

### Default Limits

- **Standard API endpoints**: 1000 requests per minute per IP
- **Login endpoint**: Subject to same limit

### Configuration

Rate limit configuration in `.env`:

```bash
# Whitelist specific IPs (comma-separated)
THROTTLE_WHITELIST=127.0.0.1,localhost
```

### Rate Limit Headers

API responses include rate limit information:

```http
X-RateLimit-Limit: 1000
X-RateLimit-Remaining: 999
Retry-After: 60
```

### Handling Rate Limits

When you hit the rate limit, you'll receive:

```json
{
  "message": "Too Many Requests"
}
```

**HTTP Status**: 429 Too Many Requests

**Solution**: Wait for the time specified in `Retry-After` header (usually 60 seconds)

## Security Best Practices

### 1. Token Storage

**Do:**
- Store tokens securely (encrypted storage, secure cookies)
- Clear tokens on logout
- Use HTTPS in production

**Don't:**
- Store tokens in plain text
- Share tokens between users
- Commit tokens to version control

### 2. Token Transmission

**Always use HTTPS** in production:

```bash
# WRONG (development only):
http://api.example.com/v1/experiments

# CORRECT (production):
https://api.example.com/v1/experiments
```

### 3. Token Validation

The API automatically validates:
- Token signature
- Token expiration
- Token format
- User existence

### 4. Password Security

**Requirements:**
- Use strong passwords (minimum 8 characters)
- Use bcrypt hashing (automatic in Laravel)
- Never send passwords in URLs
- Always use HTTPS for login

## Authentication Errors

### 401 Unauthorized

**Causes:**
- Missing Authorization header
- Invalid token
- Expired token
- Malformed token

**Response:**
```json
{
  "message": "Unauthenticated."
}
```

**Solution:**
- Check Authorization header is present
- Verify token format: `Bearer TOKEN`
- Get new token if expired

### 422 Validation Error (Login)

**Causes:**
- Missing email or password
- Invalid email format
- Incorrect credentials

**Response:**
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "email": ["The email field is required."],
    "password": ["The password field is required."]
  }
}
```

**Solution:**
- Provide both email and password
- Use correct credentials

### 429 Too Many Requests

**Cause:**
- Exceeded rate limit

**Solution:**
- Wait 60 seconds
- Add IP to THROTTLE_WHITELIST (development only)
- Implement exponential backoff

## Advanced Topics

### Custom Token Lifetime

To change token lifetime:

1. Update `.env`:
   ```bash
   JWT_TTL=720  # 12 hours
   ```

2. Clear config:
   ```bash
   php artisan config:clear
   ```

### IP Whitelisting

For development or trusted IPs:

```bash
# In .env
THROTTLE_WHITELIST=127.0.0.1,localhost,192.168.1.100
```

### Multiple User Types

The API supports different user roles/types:

- Administrator
- Primemover PY
- Primemover Runner

Authentication is the same for all user types, but permissions may differ.

## Testing Authentication

### Manual Testing

```bash
# 1. Login
TOKEN=$(curl -s -X POST 'http://localhost:8090/api/v1/login' \
  -H 'Content-Type: application/json' \
  -d '{"email": "admin@primemover.example", "password": "password"}' \
  | grep -o '"access_token":"[^"]*"' | cut -d'"' -f4)

# 2. Use token
curl -X GET 'http://localhost:8090/api/v1/experiments' \
  -H "Authorization: Bearer $TOKEN" \
  -H 'Accept: application/json'
```

### Automated Testing

In PHPUnit tests:

```php
public function test_authenticated_request()
{
    $user = User::factory()->create();
    $token = $user->createToken('Test Token')->accessToken;

    $response = $this->withHeaders([
        'Authorization' => 'Bearer ' . $token,
    ])->get('/api/v1/experiments');

    $response->assertStatus(200);
}
```

## Troubleshooting

### Token Not Working

1. **Check format**: Must be `Bearer TOKEN` (with space)
2. **Check expiration**: Default is 24 hours
3. **Check user exists**: User may have been deleted
4. **Check for typos**: Token must be exact

### Cannot Login

1. **Verify user exists**:
   ```bash
   php artisan tinker
   >>> User::where('email', 'admin@primemover.example')->first();
   ```

2. **Check password**: Use plain text, not bcrypt hash

3. **Check database connection**: Ensure database is accessible

### Rate Limited

1. **Wait 60 seconds**
2. **Check THROTTLE_WHITELIST** in `.env`
3. **Clear config**: `php artisan config:clear`

## Related Documentation

- [User Management](user-management.md) - Creating and managing users
- [API Documentation](api-documentation.md) - API endpoints and usage
- [Testing Guide](testing.md) - Testing authenticated endpoints
- [Troubleshooting](troubleshooting.md) - Common authentication issues
