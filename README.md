# FleetSimplify VBMS — Vehicle Breakdown Management System

A full-stack roadside-assistance / fleet operations platform connecting **drivers**, **mechanics**, and **administrators** in a single workflow: request roadside help, track ETA on a live OpenStreetMap, chat in real-time, pay (M-Pesa / Bank / Card), rate the mechanic, and explore breakdown analytics.

**Tech stack**: HTML, CSS, vanilla JavaScript (no frontend framework) · PHP 7.4+ with PDO · MySQL 5.7+ · Apache.

The only external JS dependency is **Leaflet 1.9** (CDN-loaded) for the map embed; charts on the admin reports page are hand-built on `<canvas>` (no Chart.js).

---

## 1. Quick start

### Prerequisites

* PHP 7.4 or later (the site is tested on PHP 8.x).
* MySQL 5.7+ or MariaDB 10.4+.
* Apache 2.4 with `mod_rewrite`, `mod_headers`, and `AllowOverride All`. (`php -S` works for quick smoke-testing.)

### 1a. Install the project files

Copy (or clone) this folder into your web server's document root:

* **XAMPP / LAMPP**: `/opt/lampp/htdocs/fleet_simplify/` (Linux) or `C:\xampp\htdocs\fleet_simplify\` (Windows). Then start Apache + MySQL from the XAMPP control panel.
* **WAMP**: `C:\wamp64\www\fleet_simplify\`.
* **MAMP**: `/Applications/MAMP/htdocs/fleet_simplify/`.
* **Plain Apache (Linux)**: somewhere readable, then point a vhost at it (see Option B below).

The site will be reachable at `http://localhost/fleet_simplify/` once Apache is running.

### 1b. Import the database

You have two options.

**Option 1 — phpMyAdmin (easiest with XAMPP/LAMPP):**

1. Open <http://localhost/phpmyadmin>.
2. Click the **Import** tab in the top bar (no need to create the database first — `sql/schema.sql` does it).
3. Choose `sql/schema.sql` from the project, then click **Import** (or **Go**) at the bottom. You should see *"Import has been successfully finished, 18 queries executed"* (or similar) and a new `fleetsimplify` database appears in the left sidebar.
4. Click **fleetsimplify** in the left sidebar to enter the database, then click **Import** again.
5. Choose `sql/seed.sql` and click **Import**. You'll see all rows inserted; refresh the table list to confirm 20 users, 20 mechanics, 30 bookings, etc.

**Option 2 — command line:**

```bash
# XAMPP/LAMPP on Linux:
/opt/lampp/bin/mysql -u root < /opt/lampp/htdocs/fleet_simplify/sql/schema.sql
/opt/lampp/bin/mysql -u root fleetsimplify < /opt/lampp/htdocs/fleet_simplify/sql/seed.sql

# Standard Linux install:
mysql -u root -p < sql/schema.sql
mysql -u root -p fleetsimplify < sql/seed.sql


:: Windows
C:\xampp\mysql\bin\mysql.exe -u root < C:\xampp\htdocs\fleet_simplify\sql\schema.sql
C:\xampp\mysql\bin\mysql.exe -u root fleetsimplify < C:\xampp\htdocs\fleet_simplify\sql\seed.sql

```

XAMPP's MySQL `root` user has **no password by default** (so omit `-p`).

The schema script creates the database `fleetsimplify` and 9 tables. The seed script populates:

| Entity            | Count |
|-------------------|-------|
| Admins            | 2     |
| Drivers (users)   | 20    |
| Mechanics         | 20 (14 approved + 6 pending) |
| Bookings          | 30 spread across the last 12 months |
| Incident reports  | 12    |
| Ratings           | 15    |
| Payments          | 18    |
| Locations         | 20 (one per mechanic, around major Kenyan cities) |
| Messages          | ~40   |

### 1c. Configure the database connection

Edit [config/db.php](config/db.php) and update the four constants — or set the matching env vars (`FS_DB_HOST`, `FS_DB_NAME`, `FS_DB_USER`, `FS_DB_PASS`):

```php
const FS_DB_HOST = 'localhost';
const FS_DB_NAME = 'fleetsimplify';
const FS_DB_USER = 'root';
const FS_DB_PASS = '';
```

For XAMPP/LAMPP defaults you typically want:

```php
const FS_DB_HOST = 'localhost';
const FS_DB_NAME = 'fleetsimplify';
const FS_DB_USER = 'root';
const FS_DB_PASS = '';   // XAMPP/LAMPP root has no password by default
```

### 1d. Serve the site

**Option A — XAMPP / LAMPP (recommended for this project):**

Start Apache + MySQL from the XAMPP control panel (or `sudo /opt/lampp/lampp start` on Linux), then open <http://localhost/fleet_simplify/>. That's it — no further config needed; XAMPP's defaults already include `mod_rewrite` and `AllowOverride All`.

**Option B — PHP built-in server (no XAMPP needed):**

```bash
cd /path/to/fleet_simplify
php -S localhost:8080
```

Open <http://localhost:8080/>. The built-in server is enough for end-to-end smoke testing — `.htaccess` is ignored, but everything else works.

**Option C — Apache vhost:**

```apache
<VirtualHost *:80>
    ServerName fleetsimplify.local
    DocumentRoot /path/to/fleet_simplify
    <Directory /path/to/fleet_simplify>
        AllowOverride All
        Require all granted
    </Directory>
    ErrorLog  ${APACHE_LOG_DIR}/fleetsimplify_error.log
    CustomLog ${APACHE_LOG_DIR}/fleetsimplify_access.log combined
</VirtualHost>
```

Add `127.0.0.1 fleetsimplify.local` to `/etc/hosts`, then `sudo systemctl reload apache2`. Verify the security rules with `curl -I http://fleetsimplify.local/config/db.php` (should return 403) and `curl -I http://fleetsimplify.local/sql/seed.sql` (also 403).

---

## 2. Default credentials

> Change these immediately in any real deployment.

| Role     | Email                            | Password   |
|----------|----------------------------------|------------|
| Admin    | `admin@fleetsimplify.local`      | `Admin@123` |
| Admin    | `ops@fleetsimplify.local`        | `Admin@123` |
| Driver   | `james.kariuki@example.com` (and 19 others) | `User@123` |
| Mechanic | `steve.karanja@autohub.co.ke` (and 19 others) | `Mech@123` |

All seed mechanics with status `approved` can sign in and operate immediately. The 6 mechanics with status `pending` will see the "Awaiting Admin Approval" screen until an admin approves them from `/admin/mechanics.php`.

---

## 3. Feature tour

### Driver
* **Find On-Road Services** — Leaflet map + filterable list of approved mechanics. "Request Service" attaches your live GPS coordinates to the booking and routes you to the chat.
* **Chat** — WhatsApp-style bubbles (sent right blue / received left grey). Polls the API every 5 s. Includes a live mini-map with the assigned mechanic's last known location and an ETA pill (haversine straight-line ÷ 35 km/h).
* **My Requests** — paginated bookings list. Completed jobs surface a 1–5 star rating modal that also captures repair time.
* **Payment** — M-Pesa, bank transfer, and card flows. Card PAN/CVV are **never persisted** — only the last-4 (`**** **** **** 1234`). Transaction reference is generated server-side as `TXN-` + 16 random hex chars.

### Mechanic
* **Dashboard** — KPIs, active jobs, "Update My Location" using `navigator.geolocation` to upsert into the `locations` table, and a notification panel that polls every 10 s. New bookings ring a Web-Audio-API two-tone alert (no audio file needed).
* **Notifications** — full-page list of incoming requests with Accept / Reject buttons.
* **Business profile** — service checkboxes (Towing, Tire Services, Brake Repairs, Battery Services, Engine Repairs), availability, address. Approval-status badge is shown at the top.
* **Customer feedback** — average score, distribution, and full review log.

### Admin
* **Drivers / Mechanics / Bookings** — full CRUD with inline edit modals and CSRF-protected POST handlers. Bookings tab supports filtering (`?tab=new|approved|rejected|completed`) and inline assign/status dropdowns.
* **Reports** — 11 hand-built canvas/SVG charts driven by live SQL `GROUP BY` queries:
  1. Breakdown causes
  2. Breakdown locations
  3. Vehicle types
  4. Repair methods
  5. Severity
  6. Downtime reasons
  7. Spare parts (parsed from comma-separated column)
  8. Service-provider workload
  9. Driver incident reports
  10. Monthly trend (current year, 12-bucket bar chart)
  11. Breakdown frequency per vehicle (top-15 horizontal bar)

Hover any slice/bar for a tooltip. Mobile widths collapse the grid to single column.

---

## 4. Security checklist

| Concern                      | Implementation                                                                                  |
|------------------------------|--------------------------------------------------------------------------------------------------|
| **SQL injection**            | All queries use `PDO::prepare` + bound parameters. `PDO::ATTR_EMULATE_PREPARES` is `false`.      |
| **Password storage**         | `password_hash($pw, PASSWORD_BCRYPT)` everywhere (cost 12). Verified with `password_verify`.     |
| **Sessions**                 | Custom name `FSSESSID`, `HttpOnly` + `SameSite=Lax`, regenerated on login, destroyed on logout, **30-min** idle timeout. |
| **CSRF**                     | Per-session token (`bin2hex(random_bytes(32))`) injected into every POST form, validated with `hash_equals`. |
| **XSS**                      | All dynamic output runs through `e()` (= `htmlspecialchars(..., ENT_QUOTES \| ENT_SUBSTITUTE)`). |
| **Input validation**         | Server-side authoritative (email via `FILTER_VALIDATE_EMAIL`, mobile must match `/^(07\|01)\d{8}$/`). Client-side validation is purely UX. |
| **Role-based access**        | `require_role('user'\|'mechanic'\|'admin')` on every page; mechanics also re-checked for `status='approved'` for sensitive features. |
| **Payment integrity**        | Amount is read from the `bookings` table server-side, never trusted from the client. `transaction_ref` and timing are server-generated. Card PAN/CVV are not stored. |
| **Direct file access**       | Top-level `.htaccess` blocks `/config/` and `/sql/` via `mod_rewrite`. Each of those folders also ships a `.htaccess` with `Require all denied`. |
| **CSRF on AJAX**             | Chat, location, and notification endpoints all require the token (POSTed in the form-data). |
| **Booking ownership**        | Chat/get-messages/send-message and rating endpoints all re-check that `booking.user_id` or `booking.mechanic_id` matches the session uid. |

Spot-check examples to run:

```bash
# Should 403 on Apache, 404 on built-in server.
curl -I http://localhost/sql/seed.sql
curl -I http://localhost/config/db.php

# CSRF rejection — should return 403.
curl -i -X POST http://localhost/api/send-message.php -d 'booking_id=1&message=hi'
```

---

## 5. Project layout

```
fleet_simplify/
├── index.php                      Landing / role select
├── .htaccess                      Apache hardening + directory blocks
├── config/
│   ├── db.php                     PDO connection
│   ├── init.php                   session + helpers (CSRF, role-gate, validators…)
│   └── .htaccess                  Require all denied
├── sql/
│   ├── schema.sql                 Database schema
│   ├── seed.sql                   Demo data
│   └── .htaccess                  Require all denied
├── partials/
│   ├── header.php  footer.php  topbar.php
│   └── sidebar-{user,mechanic,admin}.php
├── auth/
│   ├── user-{register,login}.php
│   ├── mechanic-{register,login}.php
│   ├── admin-login.php
│   └── logout.php
├── user/
│   ├── dashboard.php  find-services.php  my-requests.php
│   ├── chat.php  payment.php  profile.php
├── mechanic/
│   ├── dashboard.php  notifications.php
│   ├── update-business.php  feedback.php  profile.php  chat.php
├── admin/
│   ├── dashboard.php  drivers.php  bookings.php
│   ├── mechanics.php  feedback.php  reports.php
├── api/
│   ├── get-mechanics.php       (mechanic list, mechanic-side notifications, mechanic location for tracking)
│   ├── send-message.php        (POST chat msg)
│   ├── get-messages.php        (poll messages since id)
│   ├── update-location.php     (mechanic GPS upsert)
│   ├── submit-rating.php       (driver rates completed booking)
│   ├── approve-mechanic.php    (admin approve / reject)
│   └── booking-actions.php     (create / accept / reject / complete / assign / update_status)
└── assets/
    ├── css/{main,auth,dashboard,reports}.css
    └── js/{main,chat,gps-tracking,notifications,charts}.js
```

---

## 6. Notes & gotchas

* **Geolocation requires HTTPS** in non-`localhost` browsers. On a development LAN, browsers will refuse `navigator.geolocation` over plain HTTP — use `localhost` or set up an HTTPS vhost.
* **Web Audio beep**: browsers block auto-starting audio. The notifications script unlocks the audio context on the first user click/keydown.
* **Booking number generator** picks the next number atomically from `MAX(booking_number)` per year. For high-throughput production use, swap in a sequence table or `LAST_INSERT_ID`.
* **Payments are simulated** — M-Pesa always succeeds, Bank/Card succeed 90% of the time (deterministic random for demo). Replace `gen_txn_ref()` and the `payments` insert with a real PSP integration for production.

Enjoy.
