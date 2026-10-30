# PG Foils website

Laravel 13 + Filament 5. Public pages are server-rendered Blade with a small hand-written CSS layer; the admin is at `/admin`.
Design rules, admin spec and build plan live in `../plan/` (`DESIGN.md`, `ADMIN-SPEC.md`, `BUILD-PLAN.md`).

## First-time setup
```sh
composer setup          # install, .env, key, migrate, build assets
php artisan images:build
composer dev            # app + Vite, or: php artisan serve
```
Requires PHP 8.3+ (with gd incl. AVIF/WebP, intl, zip, sqlite3), Composer, Node 22+.

## Everyday commands
| Command | What it does |
|---|---|
| `composer test` | Runs the test suite |
| `npm run build` | Builds CSS/JS (Vite) |
| `php artisan images:build` | Makes responsive AVIF/WebP from `resources/images/source` |
| `npm run budget` | Checks the performance budget against a running server (`BUDGET_URL`) |
| `php artisan migrate --seed` | Creates the Investors Corner sections and loads the current site's 177 documents (and the three roles) |
| `php artisan admin:create` | Creates the first Admin (asks for the password). Other staff are added in the admin under Staff accounts. Each person sets up the authenticator app on first sign-in. |
| `php artisan backup:run` | Nightly backup (also scheduled at 02:00) |

## Configuration (`.env`)
- `SITE_CANONICAL_URL` public address, `https://pgfoils.in` in production.
- `STAGING_PASSWORD` when set (and `APP_ENV` is not `local`) the whole site asks for this password and is marked `noindex`.
- `SENTRY_LARAVEL_DSN` error tracking; empty means off. The owner creates the Sentry project.
- `BACKUP_ARCHIVE_PASSWORD` encrypts backup archives. Required in production.
- Production database: set `DB_CONNECTION=mysql` and the `DB_*` values (SQLite is for development).

## Performance budget
First-load JS <= 30 KB gzip, CSS <= 30 KB gzip, home page <= 1.2 MB desktop and <= 700 KB phone, LCP <= 2.5 s, CLS <= 0.05, Lighthouse mobile Performance >= 90. CI runs `npm run budget` and Lighthouse on every push.

## Security basics in place
Security headers on every response; strict Content-Security-Policy on public pages (no inline scripts or styles, so do not add any); staging password gate; encrypted nightly backups. Admin (M2): roles Admin, Editor and Reviewer; authenticator-app 2-step sign-in required for everyone, with recovery codes; sign-in throttling; 60-minute session; read-only activity log; staff accounts are deactivated, never deleted.

## Deployment notes (hosting decision is open, see `../plan/M0-REQUEST.md`)
- Document root must be `public/`. On Apache set `AllowOverride All` so `.htaccess` works.
- Run `composer install --no-dev --optimize-autoloader`, `npm ci && npm run build`, `php artisan images:build`, `php artisan migrate --force`, then `php artisan config:cache route:cache view:cache`.
- Add a cron entry: `* * * * * php /path/to/artisan schedule:run` (runs the nightly backup).
- Backups are stored on the same server by default. **Add an off-site disk** (second entry in `config/backup.php` `destination.disks`) before launch, otherwise losing the server loses the backups.
