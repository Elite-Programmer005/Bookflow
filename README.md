# BookFlow

Multi-tenant appointment booking system for small businesses built with PHP, SQLite, Tailwind CSS, and vanilla JavaScript.

## Features

- Three roles: Admin, Shopkeeper, and Customer
- Session-based authentication for Admin and Shopkeeper
- Public customer booking pages via clean tenant slugs
- Working hours, services, and booking management per shopkeeper
- Dummy payment button for demo-only booking confirmation
- SQLite database file created automatically on first run
- Demo data seeding script for quick setup

## File Layout

- `index.php` routes requests and serves the app entry points
- `config.php` sets up the database and creates tables automatically
- `auth.php` handles login, logout, and shopkeeper registration
- `functions.php` contains shared helpers and booking slot generation
- `seed.php` populates the demo database
- `admin/` contains admin-only screens
- `shopkeeper/` contains protected merchant screens
- `public/booking.php` contains the customer booking page

## Requirements

- PHP 7.4 or newer
- SQLite support in PHP, either through the native `SQLite3` extension or PDO SQLite
- Apache with `mod_rewrite` enabled for clean URLs

## Local Setup

1. Put the project folder on your PHP server.
2. Run the seeder once from the project root:

```bash
php seed.php
```

3. Start the built-in PHP server if you want a quick local preview:

```bash
php -S localhost:8000 router.php
```

4. Open `http://localhost:8000/login` in your browser.

Do not open `http://login/`. That is not a valid local URL and will fail with `ERR_NAME_NOT_RESOLVED`.

On Windows, you can also double-click `run.bat`, which starts a clean server on port `8000` and opens the correct login page automatically.

## XAMPP Option

If you prefer XAMPP, copy the `Bookflow` folder into `htdocs` and start Apache.

Then open:

```text
http://localhost/Bookflow/login
```

If Apache is already using port 80, you do not need to change the app port. The key is using the correct host and path.

For maximum compatibility, the admin and shopkeeper dashboards also support direct PHP endpoints such as `/admin/dashboard.php` and `/shopkeeper/dashboard.php`.

## Demo Accounts

- Admin: `admin@bookflow.com` / `admin123`
- Shopkeepers: created by `seed.php` with password `shop123`

## Clean URLs

The `.htaccess` file rewrites clean tenant slugs like `/salon-123` to the router. On Apache, make sure `mod_rewrite` is enabled.

## Customer Flow

Customers visit a public slug page, choose a service and date, select an available slot, fill in their contact details, and press the demo payment button. The booking is stored in SQLite and marked as confirmed for the demo payment flow.

## Shopkeeper Flow

Shopkeepers can manage services, weekly hours, bookings, and settings from the protected `/shopkeeper/*` area.

## Admin Flow

Admins can manage shopkeepers and review bookings across all tenants from the protected `/admin/*` area.

## Deployment

Upload the full folder to shared hosting or a VPS. No external database server is required because the app stores everything in the local SQLite file.

If you want to use environment overrides later, copy `.env.example` to `.env` and keep it out of version control.
