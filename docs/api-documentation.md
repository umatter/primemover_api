# API Documentation

This guide covers viewing, using, and updating the Primemover API documentation.

## Overview

The Primemover API documentation is automatically generated using Swagger/OpenAPI from PHPDoc annotations in the controller code. This ensures the documentation stays synchronized with the actual API implementation.

## Viewing Swagger Documentation

### Local Development (Native Setup)

```bash
# 1. Start your Laravel server
php artisan serve

# 2. Generate/update documentation
php artisan l5-swagger:generate

# 3. View documentation in your browser
# http://localhost:8000/api/documentation
```

### Local Development (Docker Setup)

```bash
# 1. Generate/update documentation
docker-compose exec php php artisan l5-swagger:generate

# 2. View documentation in your browser
# http://localhost:8090/api/documentation
```

### Production/Deployed Environment

For deployed instances:

- **Interactive UI**: `https://your-domain/api/documentation`
- **Raw JSON**: `https://your-domain/docs`
- **JSON File**: `storage/api-docs/api-docs.json`

## Auto-Regeneration During Development

To automatically regenerate documentation on each request during development:

1. Add to your `.env` file:
   ```bash
   L5_SWAGGER_GENERATE_ALWAYS=true
   ```

2. Clear config cache:
   ```bash
   php artisan config:clear
   ```

**Note**: This should only be enabled in development environments, not production.

## Using the Interactive Documentation

The Swagger UI provides an interactive interface to explore and test the API.

### Authenticating in Swagger UI

1. Open the documentation: http://localhost:8090/api/documentation
2. Click the "Authorize" button (usually at the top right)
3. Get an access token:
   ```bash
   curl -X POST 'http://localhost:8090/api/v1/login' \
     -H 'Content-Type: application/json' \
     -d '{"email": "admin@primemover.example", "password": "password"}'
   ```
4. Copy the `access_token` from the response
5. In the Authorize dialog, enter: `Bearer YOUR_ACCESS_TOKEN`
6. Click "Authorize" and then "Close"

Now you can test authenticated endpoints directly from the Swagger UI.

### Testing Endpoints

1. Find the endpoint you want to test
2. Click on it to expand
3. Click "Try it out"
4. Fill in required parameters
5. Click "Execute"
6. View the response below

## Alternative Documentation Viewers

If you prefer different interfaces, you can use the raw JSON with other tools:

### Swagger Editor

1. Go to [https://editor.swagger.io/](https://editor.swagger.io/)
2. Click File → Import File
3. Upload `storage/api-docs/api-docs.json`
4. Edit and view documentation

### Redoc

1. Go to [https://redocly.github.io/redoc/](https://redocly.github.io/redoc/)
2. Paste your API documentation URL
3. Get a clean, readable documentation view

### Postman

1. Open Postman
2. Click Import → Upload Files
3. Select `storage/api-docs/api-docs.json`
4. Postman will create a collection with all endpoints

### Insomnia

1. Open Insomnia
2. Click Create → Import from File
3. Select `storage/api-docs/api-docs.json`
4. Use the imported collection

## Updating Documentation

Documentation is generated from PHPDoc annotations in your controllers. When you add or modify endpoints, update the annotations and regenerate.

### Regenerate Documentation

**Native:**
```bash
php artisan l5-swagger:generate
```

**Docker:**
```bash
docker-compose exec php php artisan l5-swagger:generate
```

### Adding Documentation to New Endpoints

Use PHPDoc annotations in your controller methods:

```php
/**
 * @OA\Get(
 *     path="/api/v1/experiments",
 *     tags={"Experiments"},
 *     summary="List all experiments",
 *     description="Returns a paginated list of all experiments",
 *     operationId="getExperiments",
 *     security={{"bearer":{}}},
 *     @OA\Parameter(
 *         name="page",
 *         in="query",
 *         description="Page number",
 *         required=false,
 *         @OA\Schema(type="integer", default=1)
 *     ),
 *     @OA\Parameter(
 *         name="per_page",
 *         in="query",
 *         description="Items per page",
 *         required=false,
 *         @OA\Schema(type="integer", default=15)
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Successful operation",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(
 *                 property="data",
 *                 type="array",
 *                 @OA\Items(ref="#/components/schemas/Experiment")
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=401,
 *         description="Unauthenticated"
 *     )
 * )
 */
public function index()
{
    // Controller logic
}
```

### Common Annotation Patterns

**GET Endpoint:**
```php
/**
 * @OA\Get(
 *     path="/api/v1/resource",
 *     tags={"Resource"},
 *     summary="Get resources",
 *     security={{"bearer":{}}},
 *     @OA\Response(response=200, description="Success")
 * )
 */
```

**POST Endpoint:**
```php
/**
 * @OA\Post(
 *     path="/api/v1/resource",
 *     tags={"Resource"},
 *     summary="Create resource",
 *     security={{"bearer":{}}},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"name"},
 *             @OA\Property(property="name", type="string"),
 *             @OA\Property(property="description", type="string")
 *         )
 *     ),
 *     @OA\Response(response=201, description="Created"),
 *     @OA\Response(response=422, description="Validation Error")
 * )
 */
```

**PUT/PATCH Endpoint:**
```php
/**
 * @OA\Put(
 *     path="/api/v1/resource/{id}",
 *     tags={"Resource"},
 *     summary="Update resource",
 *     security={{"bearer":{}}},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         @OA\Schema(type="integer")
 *     ),
 *     @OA\RequestBody(/* ... */),
 *     @OA\Response(response=200, description="Updated")
 * )
 */
```

**DELETE Endpoint:**
```php
/**
 * @OA\Delete(
 *     path="/api/v1/resource/{id}",
 *     tags={"Resource"},
 *     summary="Delete resource",
 *     security={{"bearer":{}}},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         @OA\Schema(type="integer")
 *     ),
 *     @OA\Response(response=204, description="Deleted")
 * )
 */
```

## Configuration

API documentation configuration is located in `config/l5-swagger.php`.

### Common Configuration Options

```php
// Auto-generate on every request (development only)
'generate_always' => env('L5_SWAGGER_GENERATE_ALWAYS', false),

// API documentation URL
'constants' => [
    'L5_SWAGGER_CONST_HOST' => env('L5_SWAGGER_CONST_HOST', 'http://localhost'),
],

// OpenAPI version
'open_api_spec_version' => env('L5_SWAGGER_OPEN_API_SPEC_VERSION', '3.0.0'),
```

### Environment Variables

```bash
# Enable auto-generation (development only)
L5_SWAGGER_GENERATE_ALWAYS=true

# Set API host
L5_SWAGGER_CONST_HOST=http://localhost:8090

# OpenAPI spec version
L5_SWAGGER_OPEN_API_SPEC_VERSION=3.0.0
```

## Troubleshooting Documentation

### Documentation Not Showing

1. **Regenerate documentation:**
   ```bash
   php artisan l5-swagger:generate
   ```

2. **Clear config cache:**
   ```bash
   php artisan config:clear
   ```

3. **Check file permissions:**
   ```bash
   chmod -R 775 storage/api-docs
   ```

### Endpoints Missing from Documentation

1. **Check PHPDoc annotations** are present in controller
2. **Regenerate documentation**
3. **Clear route cache:**
   ```bash
   php artisan route:clear
   php artisan l5-swagger:generate
   ```

### Authentication Not Working in Swagger UI

1. **Get a fresh token** from login endpoint
2. **Format correctly**: `Bearer YOUR_TOKEN` (with space after "Bearer")
3. **Check token hasn't expired** (default is 24 hours)

### Changes Not Appearing

1. **Disable caching** in browser (Ctrl+Shift+R or Cmd+Shift+R)
2. **Clear Laravel caches:**
   ```bash
   php artisan cache:clear
   php artisan config:clear
   ```
3. **Regenerate documentation:**
   ```bash
   php artisan l5-swagger:generate
   ```

## Best Practices

1. **Keep annotations updated** with code changes
2. **Document all endpoints** including request/response examples
3. **Use consistent tags** to group related endpoints
4. **Include error responses** (401, 422, 500, etc.)
5. **Provide examples** in descriptions where helpful
6. **Test in Swagger UI** before committing
7. **Version your API** properly
8. **Document rate limits** and authentication requirements

## Related Documentation

- [API Routes](routes.md) - Complete list of available endpoints
- [Authentication](authentication.md) - How to authenticate API requests
- [Development Guide](development.md) - Development workflow and commands
- [JSON Examples](examples.md) - Request/response examples
