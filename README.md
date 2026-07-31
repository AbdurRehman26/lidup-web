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

## Billing

The subscription data model is ready for a payment provider, but checkout and
webhooks are intentionally not connected yet. Add Stripe or Paddle credentials
only when the production billing account and final plan are chosen.
