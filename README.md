# BankSathi-Style Campaign, Affiliate Link & Conversion Management Platform

A production-ready financial transaction, campaign, affiliate, tracking-link, conversion, payout, and commission management platform built with **Laravel**, **MySQL**, **AdminLTE 3 (Bootstrap 4 & jQuery)**, and dual authentication guards.

---

## Key Technical Highlights & Source-of-Truth Architecture

1. **Zero Frontend Trust**: Frontend financial values like `affiliate_payout`, `commission`, or `user_id` are ignored. The backend server acts as the sole source of truth and recalculates all splits from authenticated database records.
2. **3-Level Payout Hierarchy**:
   - **Level 1 (Advertiser → Platform)**: Gross revenue per conversion (`campaign.advertiser_payout`).
   - **Level 2 (Platform → Affiliate)**: Maximum payout allocated per affiliate (`campaign_affiliates` or `campaign.default_affiliate_payout`).
   - **Level 3 (Affiliate → End Customer)**: Customer payout chosen by affiliate (`affiliate_link.customer_payout`). Commission is computed server-side: `affiliate_commission = Level 2 - Level 3`.
3. **Immutable Conversion Snapshots**: Conversions freeze financial values into `payout_snapshots`. Editing a campaign or link later NEVER alters past historical conversions or wallet ledger entries.
4. **Double-Entry Financial Ledger**: All wallet balance mutations occur within atomic database transactions in `wallet_transactions`, preserving `balance_before` and `balance_after`.
5. **Secure Cryptographic Tokens**: Tracking links use 40-character entropy tokens (`/go/{secure_token}`) without exposing internal primary keys, user IDs, or payout amounts in public URLs.
6. **Authenticated & IP-Whitelisted Webhook Postbacks**: Incoming postbacks at `/api/v1/postback/{provider_slug}` are authenticated per provider, checked against IP CIDR whitelists, logged completely, and protected by idempotency checks (`provider_conversion_id` & `click_id`).
7. **Mobile Application Ready**: API v1 endpoints (`/api/v1/...`) are provided with Sanctum token authentication for future mobile app consumption.

---

## Dual Authentication Realms

- **Admin Control Panel**:
  - URL: `/admin/login`
  - Guard: `admin` (`admins` table, `session:admin`)
  - Features: Campaign management, Level 2 payout overrides, user management, conversion attribution audit, postback provider & IP whitelist config, wallet ledger, CSV report exports, audit logs.
  - Default Admin Credentials: `admin@platform.com` / `password123`

- **Affiliate Publisher Portal**:
  - URL: `/login` & `/register`
  - Guard: `web` (`users` table, `session:web`)
  - Features: Campaign browsing, interactive link generator with live commission preview, public link copy, click statistics, conversion tracking, wallet balance, profile & UPI info.
  - Default Demo Affiliate: `affiliate@platform.com` / `password123`

---

## Local Development Installation Guide

```bash
# 1. Clone repository & install composer dependencies
composer install

# 2. Environment file setup
cp .env.example .env

# 3. Generate application key
php artisan key:generate

# 4. Run database migrations & seeders (Creates Admin & Demo Campaign)
php artisan migrate:fresh --seed

# 5. Run automated test suite
php artisan test

# 6. Serve local development server
php artisan serve
```

Visit:
- Admin Panel: `http://localhost:8000/admin/login`
- Affiliate Portal: `http://localhost:8000/login`

---

## Automated Test Suite

Run the full automated test suite containing security attack vectors and attribution tests:

```bash
php artisan test
```

### Included Test Cases:
- `AuthenticationTest`: Dual guard isolation and unauthorized route access block.
- `CampaignAndLinkGenerationTest`: **Required Security Attack Test** (Verifies frontend manipulated payout attacks & user_id spoofing are rejected).
- `PostbackAndConversionTest`: **Required Postback Test** (Verifies unapproved IP rejection & duplicate postback idempotency).
- `ImmutablePayoutSnapshotTest`: **Required Historical Data Test** (Verifies historical conversions remain unchanged when links/campaign settings are updated later).

---

## cPanel Deployment

Refer to [`CPANEL_DEPLOYMENT.md`](file:///Users/wbsoumo/Taskwala/CPANEL_DEPLOYMENT.md) for full cPanel shared hosting setup instructions.
