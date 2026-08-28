# EasyTSK Multi-Site Subdomain Blog Engine — Deployment & Architecture Guide

## 1. Executive Summary
This application is a **High-Performance Multi-Tenant Subdomain Blog Engine** designed specifically for Shared Hosting (cPanel / Apache / LiteSpeed) with full support for monetization networks like **Adsterra**, **Monetag**, and **Google AdSense**.

### Key Architectural Strengths:
- **100% Isolated Codebase**: Runs in its own standalone directory with dedicated MySQL database (`easytsk_blogs`) or custom table prefix (`be_`).
- **Single Core Application**: 1 Laravel installation, 1 vendor directory, 1 database, 1 public directory serving 8+ subdomains (`blog1.easytsk.com`, `blog2.easytsk.com`, ..., `blog8.easytsk.com`).
- **Zero Heavy Background Processors**: Built without Redis, Supervisor, Horizon, or Docker dependencies. Operates on the standard PHP request lifecycle with file/database cache and 1 standard cPanel cron job.
- **Eloquent SiteScope Isolation**: Ensures zero accidental data leakage between blogs.

---

## 2. Default Access Credentials

- **Admin Login URL**: `https://yourdomain.com/admin/login` or `http://localhost/admin/login`
- **Email**: `admin@easytsk.com`
- **Password**: `admin123456`

---

## 3. Local Development & Testing in XAMPP

When testing locally without configuring Windows hosts or local DNS:
- **Site 1 (CryptoPulse)**: `http://localhost/Easytsk%20v2/blog_engine/public/?site=blog1`
- **Site 2 (TechVibe)**: `http://localhost/Easytsk%20v2/blog_engine/public/?site=blog2`
- **Site 3 (HealthPulse)**: `http://localhost/Easytsk%20v2/blog_engine/public/?site=blog3`
- **Admin Panel**: `http://localhost/Easytsk%20v2/blog_engine/public/admin`
- **XML Sitemap**: `http://localhost/Easytsk%20v2/blog_engine/public/sitemap.xml?site=blog1`
- **Robots.txt**: `http://localhost/Easytsk%20v2/blog_engine/public/robots.txt?site=blog1`

Or by running `php artisan serve` from `blog_engine/`:
- `http://127.0.0.1:8000/?site=blog1`
- `http://127.0.0.1:8000/admin`

---

## 4. cPanel & Shared Hosting Deployment (Step-by-Step)

### Step 1: Upload Files to cPanel
1. Compress `blog_engine` into a zip file (excluding `node_modules` or `.git`).
2. In cPanel File Manager, upload and extract it in a folder (e.g. `/home/username/blog_engine`).

### Step 2: Configure Subdomains Document Root
For each subdomain you create in cPanel (`blog1.easytsk.com`, `blog2.easytsk.com`, ..., `blog8.easytsk.com`):
- Set the **Document Root** to point to the Laravel `public` directory:
  ```
  /home/username/blog_engine/public
  ```
- *Tip: If your cPanel supports Wildcard Subdomains (`*.easytsk.com`), point `*.easytsk.com` directly to `/home/username/blog_engine/public` and you will never need to manually add subdomains in cPanel again!*

### Step 3: MySQL Database Setup
1. In cPanel **MySQL Databases**, create a database (e.g., `username_blogs`).
2. Create a user, generate a strong password, and assign all privileges.
3. Edit `/home/username/blog_engine/.env`:
   ```env
   APP_NAME="EasyTSK Blog Engine"
   APP_ENV=production
   APP_DEBUG=false
   APP_URL=https://easytsk.com

   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=username_blogs
   DB_USERNAME=username_dbuser
   DB_PASSWORD=your_secure_password

   SESSION_DRIVER=file
   CACHE_STORE=file
   QUEUE_CONNECTION=sync
   FILESYSTEM_DISK=public
   ```
4. Run migrations and seeder via cPanel Terminal or SSH:
   ```bash
   cd /home/username/blog_engine
   php artisan migrate --force
   php artisan db:seed --force
   php artisan storage:link
   ```

### Step 4: Configure Single cPanel Cron Job
In cPanel **Cron Jobs**, add a single cron job running every minute:
```bash
* * * * * /usr/local/bin/php /home/username/blog_engine/artisan schedule:run >> /dev/null 2>&1
```
*Note: This single cron handles scheduled post auto-publishing and daily analytics rollups.*

### Step 5: SSL & HTTPS
In cPanel **SSL/TLS Status**, select all subdomains (`blog1.easytsk.com` to `blog8.easytsk.com`) and click **Run AutoSSL**.

---

## 5. Ad Engine (Adsterra & Monetag Integration)

In the Admin Panel under **Ad Engine (Adsterra/Monetag)**, you can configure 10 strategic ad placement slots per site:

| Placement Slot | Location & Behavior | Recommended Ad Format |
|---|---|---|
| `header` | Above main site header billboard | 728x90 Billboard or Responsive Banner |
| `before_content` | Above the article title / introduction | Native Banner / 468x60 / Responsive |
| `in_content_p2` | Auto-injected after 2nd paragraph | High-CTR In-Article Rectangle (300x250) |
| `in_content_p5` | Auto-injected after 5th paragraph | Monetag Native Banner or Responsive |
| `after_content` | Directly below concluding paragraph | Related Widget / High CTR Display |
| `sidebar_top` | Top of the right sidebar | 300x250 or 300x600 Half Page |
| `sidebar_sticky`| Floats smoothly during scrolling | 300x250 Sticky Banner |
| `footer` | Bottom banner above footer links | 728x90 Banner |
| `popunder` | Invisible onclick script trigger | Adsterra Popunder / Monetag OnClick |
| `native_banner` | Native recommendation widgets | Multi-tile Native Grid |

Each slot can be enabled/disabled independently and cached automatically for maximum speed.

---

## 6. Task Reward Timer & Anti-Cheat Code Verification (EasyTSK Integration)

Each blog post includes an automated **Anti-Cheat 60-Second Reading Dwell Timer** and **Strict AdBlocker Enforcer**:

### How It Works:
1. **60-Second Active Dwell Time**: The countdown only proceeds when the user is actively viewing the tab (paused via Page Visibility API & Window Blur if they switch tabs).
2. **Server-Side Clock Validation**: When the timer finishes, the server cryptographically validates that 60 seconds genuinely elapsed on the server clock before issuing a one-time secret code (e.g. `TSK-9A4B2C`).
3. **Main Site Verification API**:
   - `POST /api/task/verify-code` (or `GET /api/task/verify-code?code=TSK-XXXXXX`)
   - **Sample Response**:
     ```json
     {
       "success": true,
       "valid": true,
       "message": "Secret code successfully verified and approved!",
       "data": {
         "code": "TSK-9A4B2C",
         "site_name": "CryptoPulse Insights",
         "post_title": "Understanding Bitcoin Layer 2",
         "dwell_time_seconds": 60,
         "verified_at": "2026-08-28T14:45:00Z"
       }
     }
     ```
   - **Single-Use Replay Protection**: Once verified, the code is immediately locked as `used` in the database. Subsequent verification attempts return HTTP 409 Conflict.

---

## 7. Strict Multi-Layer AdBlocker Enforcer

- **Multi-Vector Trap**: Detects uBlock Origin, AdBlock Plus, Brave Shields, and DNS sinkholes via DOM element bait traps and network script probes.
- **Enforcement Action**: Immediately freezes the 60-second reward countdown and displays a fullscreen un-dismissible blur overlay requiring the user to disable their AdBlocker to continue.

---

## 8. Subdomain ads.txt & Root Verification Files

- **`ads.txt`**: Accessible live at `https://blog1.easytsk.com/ads.txt`. Fully customizable per blog via **Admin &rarr; Site Verification & ads.txt**.
- **Root Verification Files (e.g. `sw.js` for Monetag)**: Upload or paste scripts in the Admin panel, served dynamically at root URLs like `https://blog1.easytsk.com/sw.js` or `https://blog1.easytsk.com/monetag_123.html`.
