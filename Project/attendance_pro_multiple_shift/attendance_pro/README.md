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
  - Device binding (recommend: device token / passkeys)
  - Holiday calendar UI
  - Full shift editor (window/min hours/earliest checkout)
  - Absent auto-mark cron (optional)

## Proxy / Real IP (important if you use IP restriction)
This app can enforce attendance from specific IP ranges.

- If you are **not** behind a reverse proxy/CDN, keep `trust_proxy_headers=false`.
- If you **are** behind a reverse proxy/CDN (e.g., Nginx proxy / load balancer / Cloudflare), set:
  - `trust_proxy_headers=true`
  - `trusted_proxies` to your proxy IP(s) / CIDR, or just `['cloudflare']` if you use Cloudflare

This prevents spoofing `X-Forwarded-For` and bypassing IP restrictions.


## Device restriction (Device Token)
Hardware identifier based restriction was removed.

- Employee page shows a **Device Token** (UUID) for the current browser/device.
- Admin can set per-employee **Allowed Device Token(s)** (comma-separated) to restrict check-in/out.

> Note: Device Token is not a hardware identifier. It can be copied if someone has access to it.
> For stronger security, consider WebAuthn/passkeys.

## Database migration (old installations)
If you previously had a `users.allowed_mac` column, rename it:

```sql
ALTER TABLE users CHANGE allowed_mac allowed_device_tokens VARCHAR(255) NULL;
```

If your table does not have `allowed_mac` but also does not have `allowed_device_tokens`, add it:

```sql
ALTER TABLE users ADD COLUMN allowed_device_tokens VARCHAR(255) NULL AFTER allowed_ip;
```

## Hostinger (shared hosting) tip
On Hostinger shared hosting you typically do **not** have your own Nginx reverse proxy.
So keep `trust_proxy_headers=false` unless you enabled Cloudflare (or another CDN/proxy) in front of your site.

If you enabled Cloudflare:
- set `trust_proxy_headers=true`
- set `trusted_proxies=['cloudflare']`


## Professional Build (Multiple Shifts) - Deploy on Hostinger (LiteSpeed)

### Requirements
- PHP 8.x
- MySQL (InnoDB)
- HTTPS enabled (Hostinger SSL)

### Install
1) Upload the `attendance_pro/` folder to your hosting (example: `public_html/attendance_pro/`).
2) Create a MySQL database + user in Hostinger hPanel.
3) Import `attendance_pro/database.sql` into the database.
4) Edit `attendance_pro/api/config.php` and set DB credentials.

### Office-only attendance (IP whitelist)
- In Admin → Settings, enable IP restriction and add your **office public IP**.
- Server setup: Hostinger LiteSpeed without Cloudflare → the app enforces IP using `REMOTE_ADDR` (safe).

### Login
- Default admin credentials are in `database.sql` (change immediately after first login).

### Cron (optional)
Use Hostinger Cron Jobs later for:
- daily absent mark
- monthly closing
- leave accrual (when leave module is enabled)

