# LidUp

A Laravel 13 and React marketing site and customer foundation for a macOS utility that keeps
long-running work active when the laptop lid is closed.

## Included

- Responsive product landing page
- React 19 frontend connected to Laravel through Inertia
- Registration, login, logout, and protected customer dashboard
- Subscription records with trial, renewal, and cancellation dates
- Product update records and dashboard feed
- Update mailing-list capture
- SQLite development database

## Run locally

```bash
composer install
npm install
php artisan migrate
composer run dev
```

The committed dependency lock currently requires PHP 8.4.1 or newer.

Open `http://localhost:8000`.

## Verify

```bash
php artisan test
npm run build
vendor/bin/pint --test
```

## Transactional email

Production email is delivered through Laravel's mail abstraction with Resend as
the configured transport. Add a newly generated Resend key and a sender address
from a verified domain to the production environment:

```dotenv
MAIL_MAILER=resend
MAIL_FROM_ADDRESS=notifications@mg.lidup.app
MAIL_FROM_NAME=LidUp
RESEND_API_KEY=
```

After changing production environment variables, run `php artisan optimize:clear`.
Application services should depend on `App\Contracts\TransactionalEmailSender`
instead of calling a provider SDK directly. This keeps future provider changes
isolated to configuration or a replacement adapter.

## Monitoring

Laravel Nightwatch is configured through environment variables. Keep it disabled
locally and enable it in production after adding the environment token:

```dotenv
NIGHTWATCH_TOKEN=
NIGHTWATCH_ENABLED=true
NIGHTWATCH_REQUEST_SAMPLE_RATE=0.1
NIGHTWATCH_COMMAND_SAMPLE_RATE=1.0
NIGHTWATCH_EXCEPTION_SAMPLE_RATE=1.0
```

On a Linux server, run `php artisan nightwatch:agent` as a continuously managed
background process. Restart that process after each deployment.

## Queue monitoring with Horizon

Horizon is available at `/horizon` and is restricted to authenticated LidUp
administrators outside the local environment. It requires Redis:

```dotenv
QUEUE_CONNECTION=redis
REDIS_HOST=127.0.0.1
```

Run `php artisan horizon` under Supervisor on production. The deployment
workflow calls `php artisan horizon:terminate` so Supervisor restarts Horizon
with the newly deployed code. The scheduler records Horizon metrics every five
minutes.

## GitHub deployment

Create a GitHub environment named `production`, restrict it to the `main` branch,
and add these environment secrets:

- `SERVER_IP`: production server hostname or IP address
- `SSH_USER`: SSH user that owns or can update `/var/www/lidup-web`
- `SSH_KEY`: private SSH key for that user
- `SSH_FINGERPRINT`: SHA256 fingerprint of the production SSH host key

If SSH does not use port 22, add an environment variable named `SSH_PORT`.

The deployment workflow runs only after the `tests` workflow succeeds on `main`.
It deploys the exact tested commit, installs production dependencies, builds the
React frontend, migrates the database, refreshes Laravel caches, restarts queue
workers, and reports the release to Nightwatch when Nightwatch is installed.

## Billing

The subscription data model is ready for a payment provider, but checkout and
webhooks are intentionally not connected yet. Add Stripe or Paddle credentials
only when the production billing account and final plan are chosen.

## CodeRabbit pre-commit review

Install the repository-managed Git hook after cloning:

```bash
npm run hooks:install
```

Every commit then checks the staged diff for likely credentials and runs
`coderabbit review --agent -t uncommitted`. The commit is blocked when the
CodeRabbit CLI is unavailable, unauthenticated, or the review fails.
