# Father's Heart Mobility Ministry

Laravel application for Father's Heart Mobility Ministry in Uganda. The public website is a responsive one-page introduction to the ministry, its history and objectives, photo archive, giving options, and office contact information.

> Raising the esteem and capability among people with disabilities through the Church

## Current status

- The public website is available at `/`. About, Our Work, Moments, Giving, and Contact are sections of that page.
- Earlier public paths such as `/about` and `/stories` redirect to their corresponding sections.
- Laravel Jetstream authentication and a protected dashboard route are installed. Recipient registration, distribution, inventory, reporting, and role-based admin workflows are **not yet implemented**.
- The legacy PHP application and its database have **not** been migrated into this Laravel application.

## Technology

PHP 8.2+, Laravel 12, Jetstream/Livewire, Spatie Laravel Permission, Vite, and MySQL/MariaDB for the new application database. Composer and Node.js/npm are required for local setup.

## Local setup

1. Install dependencies:

   ```powershell
   composer install
   npm install
   ```

2. Copy `.env.example` to `.env`, then generate an application key:

   ```powershell
   Copy-Item .env.example .env
   php artisan key:generate
   ```

3. Configure `.env` for the **new** database. For the WAMP setup, use `DB_CONNECTION=mysql` and `DB_DATABASE=fathersheart2`, along with your local MySQL host, port, username, and password. Set `APP_URL` to your local site URL. Never commit `.env`.

4. Verify the database name before creating tables. Back up the new database if it already contains data, then run:

   ```powershell
   php artisan migrate
   ```

5. Build assets and serve the application:

   ```powershell
   npm run build
   php artisan serve
   ```

   For WAMP/Apache, point the web server at the Laravel `public` directory. During frontend development, use `npm run dev` in a separate terminal.

Do not use `composer run setup` without reviewing it first: the standard Laravel setup script runs migrations automatically.

## Legacy data safety

The old PHP application is kept under `fathersheart1/` for reference only and is excluded from this repository. Its database is `fathersheart`; the new Laravel database is `fathersheart2`. **Never run Laravel migrations against `fathersheart`** or overwrite its tables. Any future transition should begin with a verified backup, schema/data mapping, and a tested import into the new database.

Local `.env` files, database dumps, dependencies, and generated build assets are ignored by Git. The original photo archive should be preserved; selected public images used by the website live under `public/images/`.

## Project layout

- `resources/views/website/home.blade.php` — one-page public website.
- `resources/views/layouts/website.blade.php` — public site layout and navigation.
- `resources/css/website.css` and `resources/js/app.js` — public styling and interactions.
- `public/images/slideshow/` and `public/images/stories/` — website photos.
- `routes/web.php` — public redirects and protected dashboard route.

The website does not collect online mobility requests or payments. Visitors are directed to the ministry office for requests and Uganda giving; international donation links lead to the ministry's existing donation pages.
