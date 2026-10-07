# cPanel Shared Hosting Deployment Guide for Taskwala (taskwala.co.in)
## BankSathi-Style Campaign, Affiliate Link & Conversion Management Platform

This guide provides step-by-step instructions for deploying Taskwala onto your cPanel shared hosting environment (**taskwala.co.in**).

---

### Production Deployment Overview

- **Domain**: `https://taskwala.co.in`
- **cPanel Database Host**: `localhost`
- **cPanel Database Name**: `ehcubedv_taskwala`
- **cPanel Database User**: `ehcubedv_taskuser`
- **GitHub Repository**: `https://github.com/wbsoumo/taskwala.git`

---

### Step 1: Clone or Upload Files to cPanel
1. Connect via SSH or log into **cPanel > Terminal / File Manager**.
2. If using SSH, clone the repository into your web root or project directory:
   ```bash
   git clone https://github.com/wbsoumo/taskwala.git .
   ```
3. Alternatively, upload a zip archive of the repository to your cPanel file manager and extract it.

---

### Step 2: Configure Document Root & Apache Routing
1. **Document Root**:
   - In cPanel, navigate to **Domains** or **Subdomains**.
   - Set the Document Root for `taskwala.co.in` to point directly to the `/public` subfolder (e.g., `/public_html/public`).
2. **Apache `.htaccess`**:
   - The included root `.htaccess` handles redirection to `/public` and blocks access to `.env` and `storage/`.

---

### Step 3: Production Environment `.env` Setup
Create the `.env` file in the project root:

```env
APP_NAME="Taskwala"
APP_ENV=production
APP_KEY=base64:YOUR_GENERATED_APP_KEY_HERE
APP_DEBUG=false
APP_URL=https://taskwala.co.in

APP_TIMEZONE="Asia/Kolkata"
PLATFORM_CURRENCY=INR
PLATFORM_CURRENCY_SYMBOL="₹"

LOG_CHANNEL=stack
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=info

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=ehcubedv_taskwala
DB_USERNAME=ehcubedv_taskuser
DB_PASSWORD=YOUR_DB_PASSWORD_HERE

SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=true
SESSION_PATH=/
SESSION_DOMAIN=null

QUEUE_CONNECTION=database
CACHE_STORE=database

MAIL_MAILER=smtp
MAIL_HOST=smtp.taskwala.co.in
MAIL_PORT=587
MAIL_USERNAME=noreply@taskwala.co.in
MAIL_PASSWORD=YOUR_SMTP_PASSWORD
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="noreply@taskwala.co.in"
MAIL_FROM_NAME="${APP_NAME}"

POSTBACK_IP_WHITELIST_ENABLED=true
POSTBACK_SECRET=YOUR_SECURE_POSTBACK_SECRET
TRACKING_TOKEN_LENGTH=40
CLICK_ID_PREFIX="CLK_"

SECURITY_LOGIN_MAX_ATTEMPTS=5
SECURITY_LOGIN_DECAY_MINUTES=1
AUDIT_LOGGING_ENABLED=true
```

---

### Step 4: Run Composer Install & Generate APP_KEY
In cPanel SSH terminal:

```bash
composer install --no-dev --optimize-autoloader
php artisan key:generate
```

---

### Step 5: Database Migrations & Initial Admin Account
Run schema migrations and seed the initial Super Admin account:

```bash
php artisan migrate --force
php artisan db:seed --class=AdminSeeder --force
```

> **Default Admin Account**:
> - Email: `admin@platform.com`
> - Password: `password123` *(Change password immediately after logging in!)*

---

### Step 6: Create Storage Link & Permissions
```bash
php artisan storage:link
chmod -R 775 storage bootstrap/cache
```

---

### Step 7: Enable Production Optimizations
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```

---

### Step 8: Configure cPanel Cron Job
In **cPanel > Cron Jobs**, set up a cron job running every minute (`* * * * *`):
```bash
/usr/local/bin/php /home/ehcubedv/public_html/artisan schedule:run >> /dev/null 2>&1
```

---

### Step 9: Postback Webhook URLs for Advertisers

Your live postback webhook URL endpoint will be:
`https://taskwala.co.in/api/v1/postback/{provider_slug}`

Example for Network A (`network-a`):
`https://taskwala.co.in/api/v1/postback/network-a?click_id={click_id}&conversion_id={txid}&status=approved`
