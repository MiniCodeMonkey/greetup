<p align="center">
  <img src="resources/images/greetup.png" alt="Greetup" width="200">
</p>

<h1 align="center">Greetup</h1>

<p align="center">
  A free, open source, self-hostable community events platform.<br>
  Organize local meetups and interest-based groups without paywalls.
</p>

<p align="center">
  <a href="#features">Features</a> •
  <a href="#quick-start-with-docker">Quick Start</a> •
  <a href="#manual-installation">Manual Install</a> •
  <a href="#configuration">Configuration</a> •
  <a href="#deployment">Deployment</a> •
  <a href="#contributing">Contributing</a>
</p>

---

## Features

- **Groups**: Create and join interest-based community groups
- **Events**: Schedule in-person, online, or hybrid events with RSVP management
- **Waitlists**: Automatic waitlist with fair FIFO promotion when spots open
- **Recurring events**: Weekly, biweekly, monthly, or custom recurrence
- **Discussions**: Threaded group discussions for community conversation
- **Real-time chat**: Per-event chat powered by WebSockets
- **Direct messages**: Private 1:1 messaging between members
- **Discovery**: Search and explore groups/events by interest, keyword, and location
- **Attendee check-in**: Mark attendance at events
- **Event feedback**: Post-event ratings and reviews
- **Moderation**: Report content, block users, admin dashboard for platform management
- **Role-based permissions**: Organizer, Co-Organizer, Assistant Organizer, Event Organizer, Event Host
- **No payments, no paywalls**: Every feature is free for every user

## Requirements

- Docker Desktop (for local development via Laravel Sail)
- **OR** for manual installation: PHP 8.5+, Composer, Node.js 20+, MySQL 8.0+, Redis

**Third-party API key required:**
- Geocodio API key (free tier: 2,500 lookups/day) -- [sign up at geocod.io](https://www.geocod.io)

## Quick Start with Docker

The fastest way to get Greetup running locally. Uses Laravel Sail to start all services in Docker.

```bash
# Clone the repo
git clone https://github.com/MiniCodeMonkey/greetup.git
cd greetup

# Copy environment file
cp .env.example .env

# Set your Geocodio API key (get one free at https://www.geocod.io)
# Option A: Set it in .env directly
# Option B: Export it in your shell and Sail picks it up automatically:
#   export GEOCODIO_API_KEY=your-key-here

# Install PHP dependencies (using a temporary Docker container)
docker run --rm \
    -u "$(id -u):$(id -g)" \
    -v "$(pwd):/var/www/html" \
    -w /var/www/html \
    laravelsail/php84-composer:latest \
    composer install --ignore-platform-reqs

# Start all services (app, MySQL, Redis, Meilisearch, Mailpit, queue worker, Reverb)
./vendor/bin/sail up -d

# Generate app key
./vendor/bin/sail artisan key:generate

# Install frontend dependencies and build assets
./vendor/bin/sail npm install
./vendor/bin/sail npm run build

# Run migrations and seed the demo data
./vendor/bin/sail artisan migrate --seed

# Import search indexes
./vendor/bin/sail artisan scout:import "App\\Models\\Group"
./vendor/bin/sail artisan scout:import "App\\Models\\Event"
./vendor/bin/sail artisan scout:import "App\\Models\\User"
```

**Your Greetup instance is now running at:**

| Service | URL |
|---------|-----|
| Application | http://localhost |
| Mailpit (email UI) | http://localhost:8025 |
| Meilisearch | http://localhost:7700 |

**Demo accounts after seeding:**

| Account | Email | Password |
|---------|-------|----------|
| Admin | admin@greetup.test | password |
| Regular user | user@greetup.test | password |

**Useful Sail commands:**

```bash
./vendor/bin/sail up -d          # Start all services
./vendor/bin/sail down            # Stop all services
./vendor/bin/sail artisan ...     # Run artisan commands
./vendor/bin/sail composer ...    # Run composer commands
./vendor/bin/sail npm ...         # Run npm commands
./vendor/bin/sail test            # Run tests
./vendor/bin/sail artisan queue:restart  # Restart queue worker after code changes
./vendor/bin/sail logs reverb     # View Reverb (WebSocket) logs
./vendor/bin/sail mysql           # Open MySQL CLI
./vendor/bin/sail redis           # Open Redis CLI
```

## Manual Installation (Without Docker)

For developers who prefer not to use Docker, or for production servers.

**Prerequisites:** PHP 8.5+, Composer, Node.js 20+, npm, MySQL 8.0+, Redis (recommended).

```bash
# Clone the repository
git clone https://github.com/MiniCodeMonkey/greetup.git
cd greetup

# Install PHP dependencies
composer install

# Install frontend dependencies and build assets
npm install
npm run build

# Set up environment
cp .env.example .env
php artisan key:generate

# Edit .env to configure:
# - Database credentials (DB_HOST, DB_DATABASE, DB_USERNAME, DB_PASSWORD)
# - Redis connection (REDIS_HOST, REDIS_PORT)
# - Geocodio API key (GEOCODIO_API_KEY) -- get one at https://www.geocod.io
# - Mail settings for production (MAIL_HOST, MAIL_USERNAME, etc.)

# Run migrations and seed demo data
php artisan migrate --seed

# Start the development server
php artisan serve

# In a separate terminal -- start the queue worker
php artisan queue:work

# In a separate terminal -- start the WebSocket server (for real-time chat)
php artisan reverb:start

# In a separate terminal -- start Vite dev server (for hot-reload during development)
npm run dev
```

Visit `http://localhost:8000` to access Greetup.

## Running Tests

```bash
# Using Sail (recommended)
./vendor/bin/sail test                           # Run all tests
./vendor/bin/sail test --parallel                 # Run tests in parallel (faster)
./vendor/bin/sail test --coverage --min=90        # Run with coverage report
./vendor/bin/sail test --testsuite=Unit           # Run only unit tests
./vendor/bin/sail test --testsuite=Feature        # Run only feature tests
./vendor/bin/sail artisan dusk                    # Run browser tests
./vendor/bin/sail test tests/Feature/Groups/      # Run a specific directory

# Without Sail
vendor/bin/pest
vendor/bin/pest --parallel
vendor/bin/pest --coverage --min=90
php artisan dusk
```

## Configuration

All configuration is done via the `.env` file. Key settings:

### Third-Party API Keys

```ini
# Geocodio -- REQUIRED for location features
# Free: 2,500 lookups/day. Sign up: https://www.geocod.io
GEOCODIO_API_KEY=your-api-key-here
```

Without a Geocodio key, the app works normally but location-based features (nearby events, distance sorting, map pins) are disabled. Addresses are stored as text but not geocoded.

### Database

```ini
# MySQL (default for Sail)
DB_CONNECTION=mysql
DB_HOST=mysql          # Use 'mysql' in Sail, '127.0.0.1' otherwise
DB_PORT=3306
DB_DATABASE=greetup
DB_USERNAME=sail
DB_PASSWORD=password

# PostgreSQL (alternative for production)
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=greetup
DB_USERNAME=greetup
DB_PASSWORD=secret
```

### Mail

```ini
# Local dev (Mailpit via Sail -- UI at http://localhost:8025)
MAIL_MAILER=smtp
MAIL_HOST=mailpit
MAIL_PORT=1025

# Production -- use any SMTP provider (Mailgun, Postmark, SES, Resend, etc.)
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailgun.org
MAIL_PORT=587
MAIL_USERNAME=your-username
MAIL_PASSWORD=your-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@your-greetup-instance.com
MAIL_FROM_NAME="Greetup"
```

### Search

```ini
# Meilisearch (default, included in Sail)
SCOUT_DRIVER=meilisearch
MEILISEARCH_HOST=http://meilisearch:7700  # Use 'meilisearch' in Sail
MEILISEARCH_KEY=masterKey

# Database fallback (no external dependency, slower)
SCOUT_DRIVER=database
```

### File Storage

```ini
# Default: local disk (fine for small instances)
FILESYSTEM_DISK=local

# Production: S3-compatible storage
FILESYSTEM_DISK=s3
AWS_ACCESS_KEY_ID=your-key
AWS_SECRET_ACCESS_KEY=your-secret
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=greetup-uploads
AWS_URL=https://your-cdn.com
```

### Queue

```ini
# Redis (default for Sail, recommended)
QUEUE_CONNECTION=redis
REDIS_HOST=redis       # Use 'redis' in Sail, '127.0.0.1' otherwise

# Database fallback (no Redis dependency)
QUEUE_CONNECTION=database
```

### WebSocket (Real-time Chat)

```ini
BROADCAST_CONNECTION=reverb
REVERB_APP_ID=greetup
REVERB_APP_KEY=greetup-key
REVERB_APP_SECRET=greetup-secret
REVERB_HOST=reverb          # Use 'reverb' in Sail
REVERB_PORT=8080

# Browser connects to Reverb via localhost (not the Docker hostname)
VITE_REVERB_HOST=localhost
VITE_REVERB_PORT=8080
VITE_REVERB_SCHEME=http
```

## Deployment

### Production Checklist

1. Set `APP_ENV=production` and `APP_DEBUG=false`
2. Generate a strong `APP_KEY` with `php artisan key:generate`
3. Configure a production database (MySQL or PostgreSQL recommended)
4. Set up Redis for queue, cache, and session
5. Configure SMTP for transactional email
6. Set `GEOCODIO_API_KEY` for location features (get a key at https://www.geocod.io)
7. Set up S3 or equivalent for file uploads (optional, local disk works for small instances)
8. Build frontend assets: `npm run build`
9. Run migrations: `php artisan migrate --force`
10. Import search indexes: `php artisan scout:import`
11. Optimize: `php artisan optimize`
12. Set up a queue worker (Supervisor or systemd)
13. Set up Reverb as a service for WebSocket support
14. Configure a web server (Nginx or Caddy) with HTTPS
15. Set up scheduled tasks: `* * * * * php /path/to/greetup/artisan schedule:run`

### Scheduled Commands

Add this to your crontab:

```
* * * * * cd /path/to/greetup && php artisan schedule:run >> /dev/null 2>&1
```

Greetup registers these scheduled tasks:

| Command | Frequency | Purpose |
|---------|-----------|---------|
| `events:generate-recurring` | Daily | Generate upcoming instances of recurring event series |
| `events:mark-past` | Hourly | Move ended events to `past` status |
| `accounts:purge-deleted` | Daily | Hard-delete accounts past their 30-day grace period |
| `groups:purge-deleted` | Daily | Hard-delete groups past their 90-day grace period |
| `notifications:send-digests` | Every 5 min | Send batched notification digest emails |

### Example Nginx Configuration

```nginx
server {
    listen 80;
    server_name greetup.example.com;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    server_name greetup.example.com;
    root /var/www/greetup/public;
    index index.php;

    ssl_certificate /etc/letsencrypt/live/greetup.example.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/greetup.example.com/privkey.pem;

    client_max_body_size 10M;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.5-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }

    # WebSocket proxy for Reverb
    location /app {
        proxy_pass http://127.0.0.1:8080;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "Upgrade";
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
    }
}
```

### Supervisor Configuration (Queue Worker)

```ini
[program:greetup-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/greetup/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/greetup/storage/logs/worker.log
stopwaitsecs=3600
```

### Supervisor Configuration (Reverb)

```ini
[program:greetup-reverb]
command=php /var/www/greetup/artisan reverb:start --port=8080
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=/var/www/greetup/storage/logs/reverb.log
```

## Contributing

We welcome contributions! Please see [CONTRIBUTING.md](CONTRIBUTING.md) for guidelines.

```bash
# Set up local development (using Sail is recommended -- see Quick Start above)
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
npm run dev  # in one terminal
php artisan serve  # in another terminal

# Before submitting a PR
vendor/bin/pint          # Fix code style
vendor/bin/phpstan analyse  # Static analysis
vendor/bin/pest --parallel  # Run tests
```

## License

Greetup is open-source software licensed under the [MIT License](LICENSE).
