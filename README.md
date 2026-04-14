# MCRO SMS System

This project is set up so the same codebase can run in two different ways:

- Main server PC: Laragon + MySQL
- Other PCs: plain PHP + `php artisan serve`, with or without MySQL

The important rule is: do not commit machine-specific `.env` values. Git updates the code, but each PC keeps its own `.env`.

## Environment Strategy

- `.env` is local to each machine and should stay different per PC.
- `.env.example` now defaults to a lightweight local setup:
  - `DB_CONNECTION=sqlite`
  - `SESSION_DRIVER=file`
  - `QUEUE_CONNECTION=sync`
  - `CACHE_STORE=file`
- The main server can keep using Laragon and MySQL by setting its own `.env`.

Because `.env` is not tracked by git, `git pull` on the main server should not overwrite Laragon-specific settings.

## Main Server PC (Laragon)

Use a local `.env` like this on the main server:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=http://your-laragon-host

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3307
DB_DATABASE=laravel
DB_USERNAME=root
DB_PASSWORD=

SESSION_DRIVER=file
QUEUE_CONNECTION=sync
CACHE_STORE=file

SMS_PROVIDER=textbee
TEXTBEE_BASE_URL=https://api.textbee.dev
TEXTBEE_DEVICE_ID=your_device_id
TEXTBEE_API_KEY=your_api_key
```

Notes:

- Keep the Laragon-specific MySQL port that actually matches the server PC.
- If you use a different SMS provider later, update only the server `.env`.
- Laragon can serve the app directly, so `php artisan serve` is not required there.

## Dev PC Without Laragon

For a normal PC, you can run this project without Laragon.

### Option 1: Quick local run without MySQL

Use:

```env
DB_CONNECTION=sqlite
DB_DATABASE=database/database.sqlite
SESSION_DRIVER=file
QUEUE_CONNECTION=sync
CACHE_STORE=file
SMS_PROVIDER=log
```

Then run:

```powershell
Copy-Item .env.example .env
New-Item database\database.sqlite -ItemType File -Force
php artisan key:generate
php artisan migrate
npm install
npm run build
php artisan serve
```

### Option 2: Local run with XAMPP or another MySQL server

Use a local `.env` like:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=laravel
DB_USERNAME=root
DB_PASSWORD=
SESSION_DRIVER=file
QUEUE_CONNECTION=sync
CACHE_STORE=file
SMS_PROVIDER=log
```

Then run:

```powershell
php artisan migrate
npm run build
php artisan serve
```

If you want live SMS locally too, add:

```env
SMS_PROVIDER=textbee
TEXTBEE_BASE_URL=https://api.textbee.dev
TEXTBEE_DEVICE_ID=your_device_id
TEXTBEE_API_KEY=your_api_key
```

## After Pulling New Code

Run these after `git pull` when dependencies or assets may have changed:

```powershell
composer install
npm install
php artisan optimize:clear
php artisan migrate
npm run build
```

Use `php artisan serve` only on PCs that are not being served by Laragon.

## Why This Setup Works

- The code stays shared.
- Each machine keeps its own `.env`.
- Laragon-specific settings stay on the server PC only.
- Plain-PC settings stay on the dev machine only.
- Pulling updates changes the app code, not each machine's runtime choice.
