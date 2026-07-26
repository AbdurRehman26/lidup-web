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

## Billing

The subscription data model is ready for a payment provider, but checkout and
webhooks are intentionally not connected yet. Add Stripe or Paddle credentials
only when the production billing account and final plan are chosen.
