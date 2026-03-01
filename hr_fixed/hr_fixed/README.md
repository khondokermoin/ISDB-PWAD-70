# Attendance Web App (Starter) — PHP + MySQL + JS Frontend

## Folder mapping for cPanel
Upload:
- `public/`  -> `public_html/public/`
- `api/`     -> `public_html/api/`
- `database.sql` import into MySQL database
- Copy `api/config.example.php` to `api/config.php` and set DB credentials

> Security note: do **not** commit `api/config.php` to git. Keep credentials private.

## API routing
`/api/.htaccess` routes `/api/...` requests to `api/index.php`.

## First login
- Admin user: `admin`
- Password: `Admin@123`
Change password after first login (you can add UI later).

## Notes
- This is a starter scaffold that supports:
  - Admin: employee create/activate/deactivate, settings toggle, shifts create
  - Employee: check-in/check-out + monthly report
  - Admin: daily list + monthly summary + CSV export
- Extend modules:
  - Leave requests approval UI
  - Device binding (recommend: device token / passkeys; MAC is not reliable in browsers)
  - Holiday calendar UI
  - Full shift editor (window/min hours/earliest checkout)
  - Absent auto-mark cron (optional)

## Proxy / Real IP (important if you use IP restriction)
This app can enforce attendance from specific IP ranges.

- If you are **not** behind a reverse proxy/CDN, keep `trust_proxy_headers=false`.
- If you **are** behind a reverse proxy/CDN (e.g., Nginx proxy / load balancer / Cloudflare), set:
  - `trust_proxy_headers=true`
  - `trusted_proxies` to your proxy IP(s)

This prevents spoofing `X-Forwarded-For` and bypassing IP restrictions.
