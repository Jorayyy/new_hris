# Deploying this HRIS on Hostinger

**Short answer: yes.** Hostinger runs Laravel on Web and Cloud plans (no root needed).
This app has no unusual requirements: no Redis, no Node build step, no WebSockets.
The only things Hostinger's plan must give you are **SSH, a 1-minute cron, and a MySQL database**.

Verified against this repo: `composer.json` requires `php ^8.3`, the queue/session/cache
drivers are all `database`, and the only `@vite` reference is in the unused
`welcome.blade.php` (the `/` route is the dashboard), so **no `npm run build` on the server**.

---

## 1. Does the plan fit?

| Requirement | This app needs | Hostinger Cloud | Check |
| --- | --- | --- | --- |
| PHP | 8.3 or newer (`composer.json: php ^8.3`) | selectable per website in hPanel | pick the highest available (8.3/8.4) |
| PHP extensions | `pdo_mysql`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `json`, `curl`, `fileinfo`, `zip`, `gd` | all standard | `zip` is required by `hris:backup`; `gd` by Dompdf |
| Database | MySQL / MariaDB (`json` columns, indexes) | included (MySQL 8 / MariaDB) | create DB + user in hPanel |
| SSH | `composer install`, `artisan` | yes, on Cloud and Business+ | needed for every deploy |
| Cron | **every minute** | hPanel allows 1-minute intervals | non-negotiable, see §7 |
| Queue worker | 1 queued notification class | no Supervisor on Cloud | run via cron, see §8 |
| `mysqldump` | used by `hris:backup` | present on Cloud SSH | verify in §10 |
| Redis | not used | n/a | — |
| Node/npm | not used | n/a | — |

Cloud is the right tier. Web/Business shared also works but has tighter CPU/memory limits.
Go VPS only if you later want Redis, Supervisor or a Node process.

---

## 2. What "Laravel on Hostinger" actually means

Hostinger serves everything under the website's **document root**, normally `public_html`.
Laravel must expose only its `public/` folder — the rest (`app/`, `.env`, `storage/`)
must stay unreachable over HTTP. Pick one:

**Method A — custom document root (recommended)**
Put the app in a subfolder and point the website at its `public` folder:

```
/home/uXXXXXXX/domains/yourdomain.com/
  public_html/
    hris/            <- the whole Laravel repo
      app/  bootstrap/  public/  storage/  vendor/  .env
```

Document root = `public_html/hris/public`.

In hPanel this is the **"custom folder"** option when adding a website/subdomain
(e.g. subdomain `hris.yourdomain.com` → folder `public_html/hris/public`).
If the option is not offered for the primary domain, delete and re-create the website
with the custom folder, or use a subdomain for the app and keep the bare domain
for a landing page/redirect.

**Method B — fallback when the document root cannot be changed**
Keep the app outside the web root and expose only `public/`:

```
/home/uXXXXXXX/domains/yourdomain.com/
  hris/               <- the whole Laravel repo (outside public_html)
  public_html/        <- copy of hris/public/* only
```

Then edit the two paths at the top of `public_html/index.php`:

```php
require __DIR__.'/../hris/vendor/autoload.php';
$app = require_once __DIR__.'/../hris/bootstrap/app.php';
```

**Method C — Hostinger's own guide** (whole app inside `public_html`, plus a root
`.htaccess` rewriting into `public/`). It works, but it leaves `app/`, `.env` and
`storage/` one rewrite typo away from being readable. Prefer A, then B.

---

## 3. Create the hosting account

1. hPanel → activate the Cloud plan, add your domain, wait for DNS.
2. **PHP Configuration** → PHP version ≥ 8.3, and raise:
   `memory_limit=256M`, `max_execution_time=120`, `upload_max_filesize=12M`, `post_max_size=12M`
   (the 201-file / 13th-month PDF exports and the 10 MB biometric CSV import need these).
3. **Databases → MySQL Databases** → create a database + user, note:
   host (usually `localhost`), db name, user, password.
4. **Advanced → SSH Access** → enable, note host / port / username / password.
5. **Advanced → Cron Jobs** → we add two entries later (§7, §8).
6. **SSL** → issue the free certificate, then turn on **Force HTTPS**.

---

## 4. Upload the code

Over SSH (cleanest):

```sh
ssh uXXXXXXX@your.server.ip -p 65002
cd ~/domains/yourdomain.com/public_html
git clone https://github.com/Jorayyy/new_hris.git hris
cd hris
composer install --no-dev --optimize-autoloader
```

If `composer` is not on the PATH: `php -v` to get the PHP CLI path, then
`php /usr/local/bin/composer install ...`, or run
`composer install --no-dev --optimize-autoloader` locally and upload `vendor/`.

If your repo is private, generate a deploy key (hPanel → SSH) or use
hPanel → Advanced → Git → *Connect with GitHub* (auto-deploy on push).
The Git feature only copies files — it does **not** run `composer install` or
`artisan`, so after each auto-deploy still run §11 by hand.

> Optional: delete `bootstrap/cache/packages.php` and `bootstrap/cache/services.php`
> if they exist in the upload — a dev-generated package cache breaks `artisan` on the server.

---

## 5. Configure `.env`

```sh
cp .env.example .env
nano .env
```

Production values (non-obvious ones only):

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com

LOG_CHANNEL=stack
LOG_STACK=daily
LOG_LEVEL=warning

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=uXXXXXXX_hris
DB_USERNAME=uXXXXXXX_hris
DB_PASSWORD=...

SESSION_DRIVER=database
SESSION_ENCRYPT=true

QUEUE_CONNECTION=database
CACHE_STORE=database

# Trust the Hostinger LiteSpeed/CDN proxy. Without this the app logs the proxy IP
# instead of the real client IP: the time-clock IP allowlist rejects real punches
# and the audit log is useless. `*` is fine on a single-server Cloud plan.
TRUSTED_PROXIES=*

MAIL_MAILER=smtp
MAIL_HOST=...        # your mail host
MAIL_PORT=587
MAIL_USERNAME=...
MAIL_PASSWORD=...
MAIL_FROM_ADDRESS="hr@yourdomain.com"
MAIL_FROM_NAME="${APP_NAME}"
```

`APP_LOCALE`/`APP_TIMEZONE` already default to `en_PH` / `Asia/Manila` — leave them.
Cron times in `routes/console.php` are evaluated in that timezone, so the 02:00
backup really is 02:00 Manila regardless of the server clock.

Mail matters: password-reset links and applicant/leave decision emails go through SMTP.

Then:

```sh
php artisan key:generate          # do this before migrating
```

> Back up `APP_KEY` somewhere safe. Losing it means losing encrypted data
> (including the encrypted session payload) and invalidating password-reset tokens.

---

## 6. Migrate, seed, create the first login

```sh
php artisan migrate --force
php artisan db:seed --class=ReferenceDataSeeder --force
```

**Never run a bare `php artisan db:seed` on production.** `DatabaseSeeder` has a
production guard that seeds reference data only, but the reference-only seeder is
what you actually want; the guard exists so nobody plants demo employees/payslips.

Statutory tables, leave types, document types and QA criteria come from
`ReferenceDataSeeder`. **No user is created in production**, so make the first
admin by hand:

```sh
php artisan tinker
```

```php
App\Models\User::create([
    'name' => 'System Administrator',
    'email' => 'admin@yourdomain.com',
    'password' => 'ChangeMeNow!',   // hashed by the model cast
    'role' => App\Models\User::ROLE_SUPER_ADMIN,
    'is_active' => true,
]);
```

Log in, change the password, then create HR/Payroll/Manager accounts in
Users (`super_admin`/`admin` only). An `employee` account is linked to an employee
record from the employee page.

Permissions and caching:

```sh
chmod -R 775 storage bootstrap/cache
php artisan optimize:clear
php artisan config:cache && php artisan route:cache && php artisan view:cache
```

**Every time you edit `.env`, clear and re-cache** — a cached config ignores the file.

---

## 7. Cron (the scheduler)

hPanel → Advanced → Cron Jobs → Custom:

```cron
* * * * * /opt/alt/php83/usr/bin/php /home/uXXXXXXX/domains/yourdomain.com/public_html/hris/artisan schedule:run >> /dev/null 2>&1
```

- Use Hostinger's **versioned PHP binary** (`/opt/alt/php83/usr/bin/php`), not
  `/usr/bin/php`. If web PHP and CLI PHP differ you get "could not find driver" /
  "The database driver is not configured" even though the site works. Confirm with
  `php -v` and `which php` over SSH.
- The interval must be **1 minute**. `schedule:run` only fires tasks due in that exact
  minute, so a 5-minute cron will silently skip the 02:00 backup and the 07:00/23:30 jobs.

What this drives: `attendance:mark-absent` (23:30), `documents:alerts` and
`employees:regularization-alerts` (07:00), `hris:backup` (02:00), plus the per-minute
heartbeat that the **Operations** page reads to prove cron is alive.

---

## 8. Queue

The only queued class is `App\Notifications\HrisNotification` (`ShouldQueue`) — the
notification bell and the employee emails. Without a worker they pile up in the `jobs`
table and nobody is notified.

Cloud has no Supervisor, so use a second cron entry:

```cron
* * * * * /opt/alt/php83/usr/bin/php /home/uXXXXXXX/domains/yourdomain.com/public_html/hris/artisan queue:work --stop-when-empty --tries=3 --timeout=120 >> /dev/null 2>&1
```

`--stop-when-empty` exits after draining the queue, so the next cron tick starts a fresh
process — no long-lived worker to babysit. Check the Operations page for failed jobs and
retry from there (or `php artisan queue:retry all`).

Receive-then-process granularity is 1 minute. If that lag is unacceptable, the low-volume
alternative is `QUEUE_CONNECTION=sync` (notifications sent inline during the request).

---

## 9. Backup

`php artisan hris:backup` dumps MySQL with `mysqldump` and zips `storage/app/private`
into `storage/app/backups` (7 copies kept, pruned automatically). It runs at 02:00 via
the cron in §7. Requirements on the server:

- `mysqldump` on the PATH — verify: `which mysqldump`. If missing, ask Hostinger support
  or export from phpMyAdmin instead.
- PHP `zip` extension (standard).

**Keep copies off the server.** If a plan has spare space, write to a folder outside the
app and pull it down on a schedule:

```sh
php artisan hris:backup --path=/home/uXXXXXXX/backups/hris
```

Also schedule a database export from hPanel or phpMyAdmin as a second line of defence,
and store `APP_KEY` with your password manager.

---

## 10. Verify after the first deploy

- `https://yourdomain.com/up` returns 200 (health route, no login).
- Login works; a second login from another browser stays logged in (sessions table).
- Load a payslip PDF (Dompdf works → `gd`/`dom` extensions OK).
- Upload an employee document, then download it (private disk + controller, no `storage:link` needed).
- Import a biometric CSV — the file upload limit proves `upload_max_filesize` is raised.
- **Time clock**: punch from an allowlisted IP. If it is rejected while the IP *is*
  allowlisted, `TRUSTED_PROXIES` is wrong (the app sees the proxy IP, not the client's).
- Audit log shows real client IPs.
- Run the scheduler by hand once, then confirm the heartbeat turns green on Operations:
  `php /opt/alt/php83/usr/bin/php .../artisan schedule:run`
- `php artisan hris:backup` from SSH → files appear in `storage/app/backups`.
- Request a password reset and confirm the email arrives (SMTP).
- Check `storage/logs/laravel.log` after the first day.

---

## 11. Updating the app

```sh
ssh uXXXXXXX@your.server.ip -p 65002
cd ~/domains/yourdomain.com/public_html/hris
php artisan down
git pull
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan optimize:clear
php artisan config:cache && php artisan route:cache && php artisan view:cache
php artisan up
```

If you wired hPanel → Git auto-deploy, the `git pull` is automatic — the rest still has
to run over SSH.

**Stale CSS gotcha:** `layouts/app.blade.php` links `asset('app.css')` with no version
parameter. LiteSpeed/browser caches will keep serving the old stylesheet after a deploy;
purge the cache in hPanel or hard-refresh. Adding `?v=` to that link would remove the
guesswork (not done yet).

---

## 12. Gotchas specific to this app

| Symptom | Cause | Fix |
| --- | --- | --- |
| "The database driver is not configured" / no driver | cron uses `/usr/bin/php` (different version) instead of `/opt/alt/phpXX/...` | use the versioned binary; check `php -v` |
| Scheduled jobs never ran | cron interval is 5+ minutes | set it to 1 minute |
| Notifications never arrive | no queue worker | add the §8 cron, or `QUEUE_CONNECTION=sync` |
| Everyone logged out once, unexpectedly | `SESSION_ENCRYPT=true` after users exist | expected one-time event; set it at first deploy |
| 500 on any page with a stack trace | `APP_DEBUG=true` in production | set `APP_DEBUG=false` |
| Emails from the old address | `config:cache` still holds the old `.env` | `php artisan optimize:clear` then re-cache |
| Demo employees/`admin@hris.test` on the live site | bare `db:seed` was run with `APP_ENV` not `production` | set `APP_ENV=production` before seeding |
| Backup cron fails | no `mysqldump` / missing `zip` extension | verify both on the server |
| `artisan` explodes with a class from a dev package | stale `bootstrap/cache/{packages,services}.php` from local | delete both files |
| Punches rejected, audit log shows one IP for everyone | `TRUSTED_PROXIES` empty behind Hostinger's proxy | set `TRUSTED_PROXIES=*` |
| Biometric import silently overwrites a corrected day | known behaviour: import always replaces `time_in`/`time_out` | do not re-upload old files; guard is a pending change |

`php artisan storage:link` is **not** needed: employee documents and payslips are streamed
through controllers from the private disk, nothing is served from `public/storage`.

---

## 13. One-page checklist

1. Cloud plan + domain + PHP ≥ 8.3 + SSL + Force HTTPS
2. MySQL DB/user created, credentials noted
3. App at `public_html/hris`, document root → `public_html/hris/public`
4. `composer install --no-dev --optimize-autoloader`
5. `.env` from `.env.example` with the production block from §5, then `key:generate`
6. `migrate --force` + `db:seed --class=ReferenceDataSeeder --force` + first admin via tinker
7. `chmod -R 775 storage bootstrap/cache` + `optimize:clear` + the three caches
8. Cron `schedule:run` every minute (versioned PHP path)
9. Cron `queue:work --stop-when-empty` every minute
10. SMTP configured and a reset email tested
11. Off-server backup path set, `mysqldump` verified, `APP_KEY` saved
12. Walk the §10 verification list, then change the admin password
