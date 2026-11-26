# Primemover API

Primemover is a web automation platform that allows users to create and manage browser automation experiments. It provides APIs for configuring crawlers, managing browser agents, and executing automated web interactions.

## Features

- **Experiment Management**: Create and configure web automation experiments
- **Browser Automation**: Control browser behavior through agents and proxies
- **Queue Management**: Schedule and monitor automation tasks
- **Data Collection**: Gather and store results from web interactions

## Quick Start (Docker)

> **Run `docker compose` from this repository's root.** The compose file uses relative
> bind mounts for MySQL/Redis data and logs (`./storage/app/data/mysql`,
> `./storage/app/data/redis`, `./storage/logs/*`). If launched from the wrong directory,
> Docker silently creates those paths as empty dirs in your CWD and spins up a fresh,
> empty database there instead of using this repo's `storage/`.

```bash
# Clone and setup
git clone <repository-url> primemover-api
cd primemover-api
cp .env.docker .env

# Start all containers (use 'docker-compose' if docker compose v2 not available)
docker compose up -d --build

# Install dependencies and initialize
docker compose exec php composer install
docker compose exec php php artisan key:generate
docker compose exec php php artisan jwt:secret --force
docker compose exec php php artisan migrate --seed
docker compose exec php php artisan passport:install --force
docker compose exec php php artisan l5-swagger:generate

# Test the API
curl -X POST 'http://localhost:8090/api/v1/login' \
  -H 'Content-Type: application/json' \
  -d '{"email": "admin@primemover.example", "password": "password"}'
```

**Access Points:**
- API: http://localhost:8090
- Documentation: http://localhost:8090/api/documentation
- Telescope: http://localhost:8090/telescope

**Default credentials:** `admin@primemover.example` / `password`

## 🤖 AI Assistant Integration (MCP Server)

The API includes a **Model Context Protocol (MCP) server** that lets AI assistants like Claude interact with the API through documentation and read-only operations.

**Quick Setup for Claude Code:**

```bash
# Docker (recommended)
claude mcp add --transport stdio primemover \
  --env PM_API_EMAIL=admin@primemover.example \
  --env PM_API_PASSWORD=password \
  -- /absolute/path/to/primemover_api/mcp-server-docker.sh

# Verify
claude mcp list
/mcp  # within Claude Code
```

**What it provides:**
- 📚 Full OpenAPI specification and documentation
- 🔍 List and query experiments, crawlers, queues, jobs
- 📥 Download experiment data and reports
- 🔒 Read-only access (safe for exploration)

See **[MCP_SERVER_SETUP.md](MCP_SERVER_SETUP.md)** for complete instructions and Claude Desktop setup.

## Accounts and passwords

| what | how |
|---|---|
| sign in (GUI, scripts) | `POST /api/v1/auth/token` with `username` + `password` (Passport password grant; the client secret is injected server-side). A wrong password is **400 `invalid_grant`**, not 401. |
| change own password | `POST /api/v1/auth/change-password` (bearer auth) with `current_password`, `password`, `password_confirmation`. Revokes the user's other tokens. |
| forgot password | `POST /api/v1/forgot-password` emails a link to `URL_FRONTEND/password/reset/<token>?email=…`; `POST /api/v1/reset-password` completes it. A dead mail relay is a clean 503, not a stack trace. |
| admin fallback | `php artisan user:set-password <email>` prompts for the password (never pass it as an argument or inside a tinker one-liner — `$` in a double-quoted PHP string has already stored the wrong value once). |

Password rule everywhere: at least 8 characters, one uppercase, one digit, one
special character.

Reset mail needs two things in `.env`: `URL_FRONTEND` (where the GUI is served
— the link is built on it, via `config('app.frontend_url')`) and a working
`MAIL_*` relay (`MAIL_MAILER=smtp`, host/port/encryption/credentials,
`MAIL_FROM_ADDRESS` matching the relay's sender). Mailtrap is a sandbox and
delivers nothing.

## Deployment notes

- **Debug off in any deployment**: `APP_ENV=production`, `APP_DEBUG=false`.
  With debug on, every unhandled exception returns its full stack trace to the
  browser. Error detail still lands in `storage/logs/laravel.log`.
- **ex63 edge**: `docker-compose.ex63.yml` is an opt-in overlay that joins
  **both `apache` and `php`** to the shared `ex63_internal` network and routes
  `https://primemover.${DOMAIN}/api` to apache (same origin as the GUI — no CORS,
  no second hostname). Two containers, for opposite reasons: apache so traefik can
  reach **in**, php so the object-store disks can reach **out** to MinIO. Apply with
  `docker compose -f docker-compose.yml -f docker-compose.override.yml -f docker-compose.ex63.yml up -d apache php`;
  needs `DOMAIN` in `.env`. The base compose stays portable and untouched.
  Omitting php is silent: every object read returns nothing rather than failing.
- **Object storage — two disks, two buckets, neither is AWS**:
  `s3runner` (read-only, bucket `primemoverrunner`) serves the runner's job data
  through `GET /file/{report}` and `GET /jobs/{id}/decision`. The plain `s3` disk
  (bucket `primemover-api`, its own scoped read-write key) holds what the API
  itself creates — experiment export zips and `POST /upload`. Both point at the
  local MinIO via `http://minio:9000`; the public hostname answers 403 from a
  docker bridge address. `AWS_MOCK` is not a test double: it selects where the
  `s3` disk lives (`false` = MinIO, `true` = the local disk in the container),
  and `AWS_DEFAULT_REGION` is required by both even though MinIO ignores it.
  Setup: `docs/minio-api-bucket.md`.
- **Tests log nowhere**: `phpunit.xml` sets `LOG_CHANNEL=null` (a channel
  defined in `config/logging.php`). Tests run in the same container as the live
  API and used to write `testing.ERROR` lines into the production log.

## Technology Stack

- **Backend**: Laravel 10, PHP 8.1+, MySQL 8.0, Redis
- **Authentication**: Laravel Passport (OAuth 2.0), JWT tokens
- **Infrastructure**: Docker, Apache/Nginx, AWS S3
- **Frontend**: Angular (separate repository)

## Architecture

```
Experiment → Crawler → Configuration (params, preferences)
                    → Agent (browser identity)
                    → Proxy (network routing)
                    → Queue → Jobs (visit, click, scroll, wait)
                           → Behaviors (job parameters)
```

`experiments.runner_user_id` decides which runner box actually executes this pipeline
(unset = default runner); see the orchestrator repo's `docs/integration-contract.md`
"Runner assignment" for the full mechanism.

## 📚 Documentation

### Getting Started
- **[Installation Guide](docs/installation.md)** - Complete setup instructions for Docker and native environments
- **[User Management](docs/user-management.md)** - How to create and manage users (registration is disabled)
- **[Testing Guide](docs/testing.md)** - Test the API locally and on deployed environments
- **[Authentication Guide](docs/authentication.md)** - OAuth 2.0, JWT tokens, and rate limiting

### Development
- **[Development Guide](docs/development.md)** - Workflow, commands, and code standards
- **[API Documentation Guide](docs/api-documentation.md)** - Working with Swagger/OpenAPI docs
- **[Troubleshooting](docs/troubleshooting.md)** - Common issues and solutions

### Reference
- **[Infrastructure](docs/infrastructure.md)** - Server information and access
- **[Environment Setup](docs/setup.md)** - Traditional server setup
- **[Production Deployment](docs/setup_production.md)** - Production deployment best practices
- **[Use Cases](docs/usecases.md)** - Common experiment configurations
- **[API Routes](docs/routes.md)** - Complete list of endpoints
- **[JSON Examples](docs/examples.md)** - Request/response formats

## Contributing

1. Create feature branch: `git checkout -b feature/your-feature`
2. Make changes and add tests
3. Run tests: `php artisan test` and linting: `composer lint`
4. Submit a pull request

See the [Development Guide](docs/development.md) for detailed workflow and code standards.

## Funding

Development of PrimeMover was funded by the Swiss National Science Foundation (SNSF),
grants 100018_207698 and CRSK-1_190429.

## License

This project is licensed under the MIT License.

---

**Need help?** Check the [Troubleshooting Guide](docs/troubleshooting.md) or review the [Installation Guide](docs/installation.md) for setup issues.
