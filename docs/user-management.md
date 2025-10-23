# User Management

This guide covers how to create and manage users in the Primemover API.

## User Registration Policy

**Public registration is disabled** for security reasons. Users must be added manually by administrators with database access.

The `/register` endpoint is intentionally disabled to prevent unauthorized account creation. This is a security measure to ensure only authorized personnel can access the system.

## How to Add Users

Since public registration is disabled, new users must be created directly through database access. You'll need:

- Database access (MySQL credentials or Docker container access)
- User's name, email, and desired password

### Method 1: Using Laravel Tinker (Recommended)

Laravel Tinker provides an interactive REPL for Laravel applications, making it easy to create users programmatically.

**For Docker setup:**
```bash
# Access the application container
docker-compose exec php php artisan tinker

# Create a new user
User::create([
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'password' => bcrypt('SecurePassword123!')
]);

# Verify user was created
User::where('email', 'john@example.com')->first();

# Exit tinker
exit
```

**For native setup:**
```bash
# Access tinker
php artisan tinker

# Create a new user
User::create([
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'password' => bcrypt('SecurePassword123!')
]);

# Verify user was created
User::where('email', 'john@example.com')->first();

# Exit tinker
exit
```

### Method 2: Direct Database Access

If you have direct access to the MySQL database, you can insert users with SQL.

**For Docker setup:**
```bash
# Access MySQL container
docker-compose exec mysql mysql -u primemover -p primemover
# Password: secret

# Insert user
INSERT INTO users (name, email, password, created_at, updated_at)
VALUES (
    'John Doe',
    'john@example.com',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    NOW(),
    NOW()
);

# Verify user was created
SELECT * FROM users WHERE email = 'john@example.com';

# Exit MySQL
exit
```

**For native setup:**
```bash
# Connect to MySQL
mysql -u primemover -p primemover
# Enter your password

# Insert user
INSERT INTO users (name, email, password, created_at, updated_at)
VALUES (
    'John Doe',
    'john@example.com',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    NOW(),
    NOW()
);

# Exit MySQL
exit
```

**Note**: The password hash shown above is for `password`. For production, generate a secure hash:
```bash
php artisan tinker
>>> bcrypt('YourSecurePassword');
```

### Method 3: Using Database Seeder

For adding multiple users or setting up test environments, you can use Laravel's seeding functionality.

1. Add users to `database/seeders/DatabaseSeeder.php`:

```php
public function run()
{
    User::create([
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'password' => bcrypt('SecurePassword123!'),
    ]);

    User::create([
        'name' => 'Jane Smith',
        'email' => 'jane@example.com',
        'password' => bcrypt('AnotherSecure123!'),
    ]);
}
```

2. Run the seeder:

**For Docker:**
```bash
docker-compose exec php php artisan db:seed
```

**For Native:**
```bash
php artisan db:seed
```

## User Authentication

Once users are created, they can authenticate using the API's login endpoint.

### Login Request

```bash
curl -X POST 'http://localhost:8090/api/v1/login' \
  -H 'Content-Type: application/json' \
  -H 'Accept: application/json' \
  -d '{
    "email": "john@example.com",
    "password": "SecurePassword123!"
  }'
```

### Expected Response

```json
{
  "user": {
    "id": 5,
    "name": "John Doe",
    "email": "john@example.com",
    "email_verified_at": null,
    "created_at": "2024-01-15T10:30:00.000000Z",
    "updated_at": "2024-01-15T10:30:00.000000Z",
    "photo_url": "https://www.gravatar.com/avatar/..."
  },
  "access_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9..."
}
```

The `access_token` should be used in subsequent API requests as a Bearer token.

## Managing Existing Users

### List All Users

```bash
docker-compose exec php php artisan tinker

# List all users
User::all();

# Count users
User::count();
```

### Update User

```bash
docker-compose exec php php artisan tinker

# Find and update user
$user = User::where('email', 'john@example.com')->first();
$user->name = 'John Updated';
$user->save();
```

### Delete User

```bash
docker-compose exec php php artisan tinker

# Find and delete user
$user = User::where('email', 'john@example.com')->first();
$user->delete();
```

### Reset Password

```bash
docker-compose exec php php artisan tinker

# Find user and reset password
$user = User::where('email', 'john@example.com')->first();
$user->password = bcrypt('NewSecurePassword123!');
$user->save();
```

## Default Users

The database seeder creates these default test users:

- **Administrator**: `admin@primemover.example` / `password`
- **Primemover PY**: `researcher@primemover.example` / `password`
- **Primemover Runner**: `runner@primemover.example` / `password`

**Important Security Note**: These default users should be removed or have their passwords changed in production environments.

## Best Practices

1. **Use Strong Passwords**: Always use strong, unique passwords for each user
2. **Regular Audits**: Periodically review user accounts and remove inactive ones
3. **Role-Based Access**: Consider implementing role-based permissions if needed
4. **Email Verification**: Consider adding email verification for added security
5. **Password Policies**: Enforce password complexity requirements
6. **Account Lockout**: Consider implementing account lockout after failed login attempts

## Related Documentation

- [Authentication Guide](authentication.md) - Details on OAuth, JWT, and API authentication
- [API Routes](routes.md) - Available API endpoints
- [Testing Guide](testing.md) - How to test API with user accounts
