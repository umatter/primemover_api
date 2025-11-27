# Clear Test Data Command

A convenient Artisan command and shell script for clearing all experiment-related test data while preserving user accounts and system data.

## Purpose

During development and testing, you often need to reset the database without losing user accounts or completely rebuilding the database. This command provides a safe way to clear all experimental data while keeping the application structure intact.

## What Gets Deleted

The following data will be **permanently deleted**:

- ✗ Experiments
- ✗ Crawlers
- ✗ Agents
- ✗ Proxies
- ✗ Configurations, Params, Preferences
- ✗ Queues, Jobs, Behaviors
- ✗ Terms (search terms)
- ✗ Outlets (media outlets)
- ✗ Reports
- ✗ Posts

## What Gets Preserved

The following data will be **preserved**:

- ✓ Users and authentication data (passwords, tokens)
- ✓ OAuth providers
- ✓ Failed jobs log
- ✓ Telescope debugging data
- ✓ Database structure (tables, columns, indexes)

## Usage

### Option 1: Shell Script (Recommended)

The shell script automatically detects whether you're running in Docker or native environment:

```bash
# Interactive mode (asks for confirmation)
./clear-test-data.sh

# Force mode (no confirmation prompt)
./clear-test-data.sh --force
```

### Option 2: Direct Artisan Command

#### Docker Environment

```bash
# Interactive mode
docker-compose exec php php artisan data:clear-test

# Force mode
docker-compose exec php php artisan data:clear-test --force
```

#### Native Environment

```bash
# Interactive mode
php artisan data:clear-test

# Force mode
php artisan data:clear-test --force
```

## Examples

### Interactive Mode (Default)

```bash
$ ./clear-test-data.sh

╔════════════════════════════════════════════════════════════════╗
║         Primemover API - Clear Test Data Script               ║
╚════════════════════════════════════════════════════════════════╝

🐳 Running in Docker environment...
⚠️  This will DELETE ALL experiment-related data from the database!
   The following will be cleared:
   - Experiments
   - Crawlers
   - Agents
   ...

 Are you sure you want to proceed? (yes/no) [no]:
 > yes

Starting data cleanup...

✓ Deleted 1440 records from Behaviors
✓ Deleted 585 records from Jobs
✓ Deleted 23 records from Crawlers
✓ Deleted 23 records from Experiments

✓ Database cleanup completed successfully!
```

### Force Mode (No Confirmation)

```bash
$ ./clear-test-data.sh --force

╔════════════════════════════════════════════════════════════════╗
║         Primemover API - Clear Test Data Script               ║
╚════════════════════════════════════════════════════════════════╝

🐳 Running in Docker environment...
⚠️  This will DELETE ALL experiment-related data from the database!
...

Starting data cleanup...

✓ Deleted 1440 records from Behaviors
...
✓ Database cleanup completed successfully!
```

## When to Use

- **Testing**: After running tests, clear data before the next test run
- **Development**: Reset to a clean state while keeping your user account
- **Demo preparation**: Clear old demo data before showing the application
- **Data corruption**: Remove corrupted experiment data without rebuilding the entire database

## Safety Features

1. **Confirmation prompt**: By default, requires explicit confirmation before deletion
2. **Force flag**: `--force` option for automated scripts (use with caution)
3. **Preserves critical data**: Users, authentication, and system tables are never touched
4. **Transaction safety**: Uses proper foreign key handling to prevent constraint violations
5. **Error handling**: Gracefully handles errors and ensures database consistency

## Technical Details

- **Command class**: `App\Console\Commands\ClearTestData`
- **Signature**: `data:clear-test {--force}`
- **Database operations**: Disables foreign key checks during deletion, then re-enables them
- **Deletion order**: Child tables first (behaviors, jobs) → Parent tables last (experiments)

## Troubleshooting

### "SET FOREIGN_KEY_CHECKS=0" fails

If you get an error about foreign key checks, ensure your database user has the necessary permissions:

```sql
GRANT ALL PRIVILEGES ON primemover.* TO 'primemover'@'%';
FLUSH PRIVILEGES;
```

### Script doesn't detect Docker

Make sure Docker containers are running:

```bash
docker-compose ps
```

If the PHP container isn't running, start it:

```bash
docker-compose up -d
```

## Alternative: Complete Database Reset

If you need to reset **everything** including users, use:

```bash
# Docker
docker-compose exec php php artisan migrate:fresh --seed

# Native
php artisan migrate:fresh --seed
```

This will rebuild the entire database from scratch with seed data.
