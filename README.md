# Fleet & Rental System

Internal Indonesian/WIB fleet and rental administration application.

## Architecture

The application is a Laravel modular monolith targeting PostgreSQL in production, with server-rendered Blade/Tailwind UI and Vite assets. The layers are deliberately separated: framework-free Domain value objects and rules; Application use cases, ports, query services, and transaction orchestration; Infrastructure Eloquent, database, clock, authentication, and transaction adapters; and Presentation controllers/routes/views. This keeps date, pricing, normalization, lifecycle, overlap, and no-partial-mutation rules testable without putting persistence decisions in controllers.

The bounded modules are Auth, Vehicles/Brand-Type, Customers, Rentals, Dashboard, and Lifecycle History. PostgreSQL is the production target configured by `.env.example`; the current runtime evidence reported SQLite, so PostgreSQL connectivity and PostgreSQL-specific concurrency/constraint behavior are not claimed here.

## Requirements and business rules

Vehicle records use separate Brand and Type values, normalized unique plates, integer USD cents, optional year/color, archive/restore, derived status, search/filter/pagination, and no hard delete. Customers have normalized unique email/Indonesian mobile values, editable details, retention, and no delete/archive. Access is restricted to the seeded environment Admin identity; no public registration, password reset, customer login, or public API is provided.

### Overlap logic

Dates are inclusive WIB calendar dates. A requested rental overlaps an existing blocking rental for the same vehicle when `requested_start <= existing_effective_end AND requested_end >= existing_start`; therefore boundary-touching dates conflict. Booked and active rentals block, completed rentals do not block after their effective end, and availability preview is advisory while save-time validation repeats the rule inside the transaction boundary. Archived vehicles are not selectable for new rentals.

### Pricing and lifecycle

Duration is `(end - start) + 1`, so a same-day rental is one day. Daily rates and snapshots are integer cents. Rentals longer than seven days receive a 10% discount and the final cent amount uses half-up rounding; the stored snapshot is not rewritten by later vehicle-rate changes. Booked, active, completed, and cancelled lifecycle rules use the injected WIB clock, with lifecycle history and state-change guards. The exact inclusion semantics for `today_rental_total` and the reuse interpretation for cancellation before a booked start remain product decisions and are intentionally unresolved.

## Setup and usage

Prerequisites are PHP 8.3+, Composer, Node.js/npm, and a configured database. Production is intended for PostgreSQL; copy `.env.example` to `.env` through the deployment process, set a real `APP_KEY`, PostgreSQL connection values, and environment-only `ADMIN_USERNAME`/`ADMIN_PASSWORD`. Never commit, print, log, or render credentials or customer data. Local development may use the generated environment configuration, but production must set `APP_DEBUG=false`, use TLS, and set `SESSION_SECURE_COOKIE=true` with secure deployment/session settings.

The actual Composer scripts are defined in `composer.json`: `composer setup`, `composer dev`, and `composer test`. The actual frontend scripts in `package.json` are `npm run build` and `npm run dev`. Inspect the manifests before using other commands. With `SESSION_DRIVER=database` and `CACHE_STORE=database`, run `php artisan migrate --seed` before opening the app: it creates application, `sessions`, and `cache` tables, then seeds the environment-configured Admin. Generate the application key with `php artisan key:generate`, create the public storage link with `php artisan storage:link`, then start `php artisan serve` and `npm run dev`. The root URL redirects to `/login`.

## Verification evidence

The supplied final evidence records:

- `composer test` passed: 24 tests, 79 assertions, including config clear.
- `composer validate --no-check-publish` passed.
- `npm run build` passed with only the optional `fontaine` optimization warning.
- `php artisan route:list` reported 24 routes.
- `php artisan about` reported Laravel 13.26.1, PHP 8.5.1, timezone `Asia/Jakarta`, runtime database SQLite, session/cache database, and storage link not linked.
- `git diff --check` passed.

Migration execution, database connectivity, PostgreSQL concurrency, and PostgreSQL-specific constraint evidence are not claimed. Browser/manual accessibility evidence is also not claimed by the automated checks and remains a parent review item.

## Security and deployment requirements

Use HTTPS/TLS in production, set `APP_DEBUG=false`, keep the seeded Admin credential only in deployment environment configuration, and do not expose secrets in UI, logs, documentation, or client output. Production session cookies must be secure, HTTP-only, and configured with an appropriate SameSite policy. Keep authentication and CSRF protection enabled for state changes, validate and authorize every mutation server-side, preserve customer confidentiality, and do not expose public account/API paths. PostgreSQL TLS mode and certificate/trust configuration must be selected and verified by deployment owners.
