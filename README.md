# Father's Heart Mobility Ministry

Laravel application for Father's Heart Mobility Ministry in Uganda. The public website is a responsive one-page introduction to the ministry, its history and objectives, photo archive, giving options, and office contact information.

> Raising the esteem and capability among people with disabilities through the Church

## Current status

- The public website is available at `/`. About, Our Work, Moments, Giving, and Contact are sections of that page.
- Earlier public paths such as `/about` and `/stories` redirect to their corresponding sections.
- Staff login and an admin dashboard are available. Public registration is disabled. Staff can manage people, assessments, locations, distribution events, and inventory. Confirmed device handovers and detailed reports remain planned.
- Inventory starts with an explicitly approved opening count per item; stock received and issued is then recorded in a ledger. Legacy inventory snapshots are not assumed to be verified balances.
- Assessment photos are stored on the private local disk. A recorded consent decision is separate from storing a photo; do not publish images without appropriate consent.
- New assessment codes are generated on save from the assessment month, a sequence, and the person's initials. Administrators can adjust future-code settings under **Intake settings**; existing codes are preserved.
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

5. Seed the roles and ministry permission catalogue in **fathersheart2**:

   ```powershell
   php artisan db:seed --class=RoleSeeder
   ```

   This creates the canonical `Super Administrator` role and preserves the earlier `super-admin` role as a working alias. Both receive every available permission, as in EBIMS.

   To create Cedar's first administrator account, set `SUPER_ADMIN_NAME`, `SUPER_ADMIN_EMAIL`, and a unique `SUPER_ADMIN_PASSWORD` of at least 12 characters in your local `.env`. Do not share or commit the password. Then run:

   ```powershell
   php artisan db:seed --class=SuperAdminSeeder
   ```

   The account seeder hashes the password and checks the database name before writing. Rerunning it preserves an existing super administrator's name and password; it refuses to promote an unrelated account with the same email. With credentials configured, `php artisan db:seed` also runs this account seeder automatically. Without them, it seeds roles and permissions only. If configuration was cached before adding these values, run `php artisan config:clear` first. Sign in at `/login`; super administrators can open `/dashboard`.

6. Build assets and serve the application:

   ```powershell
   npm run build
   php artisan serve
   ```

   For WAMP/Apache, point the web server at the Laravel `public` directory. During frontend development, use `npm run dev` in a separate terminal.

Do not use `composer run setup` without reviewing it first: the standard Laravel setup script runs migrations automatically.

## Legacy data safety

The old PHP application is kept under `fathersheart1/` for reference only and is excluded from this repository. Its database is `fathersheart`; the new Laravel database is `fathersheart2`. **Never run Laravel migrations against `fathersheart`** or overwrite its tables. Any future transition should begin with a verified backup, schema/data mapping, and a tested import into the new database.

Run `php artisan legacy:audit` to count source tables and identify tables shaped like recipient records. It writes a private aggregate report under `storage/app/private/legacy-audits`. The command does not import records or verify backups, and counts may change while the old system is in use. The source contains both MyISAM and InnoDB tables; existing `fathersheart2` application tables use MyISAM. A reviewed backup and transactional migration strategy are required before historical data is copied.

Local `.env` files, database dumps, dependencies, and generated build assets are ignored by Git. The original photo archive should be preserved; selected public images used by the website live under `public/images/`.

## Project layout

- `resources/views/website/home.blade.php` — one-page public website.
- `resources/views/layouts/website.blade.php` — public site layout and navigation.
- `resources/css/website.css` and `resources/js/app.js` — public styling and interactions.
- `public/images/slideshow/` and `public/images/stories/` — website photos.
- `routes/web.php` — public redirects and protected dashboard route.
- `resources/views/admin/dashboard.blade.php` — the first super-admin dashboard.
- `database/seeders/RoleSeeder.php` and `SuperAdminSeeder.php` — safe admin setup.

The website does not collect online mobility requests or process payments itself. Visitors are directed to the ministry office for requests and Uganda giving; country-specific donation instructions are on this website, with direct links to external payment partners where required. Verify all partner details with the ministry before launch.
