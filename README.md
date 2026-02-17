# Klaude Assessment

A Shopify integration service built with PHP (Laravel) and Node.js. Supports OAuth authentication, GraphQL-based data synchronization, and real-time webhook processing.

**Documentation:**

- [Setup & Usage Guide](README.md) (You are here)
- [Technical Explanation](EXPLANATION.md) - Implementation decisions

## Table of Contents

- [Overview](#overview)
- [Prerequisites](#prerequisites)
- [Store Domain Format](#store-domain-format)
- [Shopify App Setup](#shopify-app-setup)
- [Installation](#installation)
- [Environment Configuration](#environment-configuration)
- [Running the Services](#running-the-services)
- [Shopify Configuration](#shopify-configuration)
- [Testing the Application](#testing-the-application)
- [Project Structure](#project-structure)
- [Security Notes](#security-notes)
- [Troubleshooting](#troubleshooting)

---

## Overview

This integration service provides:

- **OAuth 2.0 authentication** with Shopify stores
- **Data synchronization** for products and orders using GraphQL Admin API
- **Real-time webhooks** for product updates and order creation
- **Secure token storage** with encryption at rest

**Technology Stack:**

- Laravel 12 (PHP)
- Node.js with Express
- PostgreSQL
- Shopify GraphQL Admin API

---

## Prerequisites

- PHP 8.2 or higher
- Composer
- Node.js 18+ and npm
- PostgreSQL 14+
- ngrok (for webhook testing)
- Shopify Partner account
- Shopify development store

---

## Store Domain Format

Shopify store domains follow this format:

```
store-name.myshopify.com
```

**Examples:**

- `my-test-store.myshopify.com`
- `johns-shop.myshopify.com`
- `dev-store-123.myshopify.com`

**Validation:**

- Must end with `.myshopify.com`
- Store name can contain alphanumeric characters and hyphens
- Cannot start with a hyphen

---

## Shopify App Setup

### Step 1: Create Shopify Partner Account

1. Go to [Shopify Partners](https://partners.shopify.com)
2. Sign up for a free account
3. Complete verification

### Step 2: Create Development Store

1. In Partner Dashboard, click **Stores**
2. Go to [Shopify Dev Dashboard](https://dev.shopify.com/dashboard)
3. Click on **Dev stores**
4. Click **Add dev store**
5. Fill in store details
6. Click **Create store**

### Step 3: Create App

1. Go to [Shopify Dev Dashboard](https://dev.shopify.com/dashboard)
2. On Apps, click **Create app**
3. Pick **Start from Dev Dashboard**
4. Enter app name (e.g., "My Integration App")
5. Click **Create**

### Step 4: Configure App

**App URL:**

```
https://your-domain.com/shopify/callback
```

(For development, use ngrok URL)

**Redirect URLs:**

```
https://your-domain.com/shopify/callback
```

### Step 5: API Scopes Requested

This app requires the following scopes:

| Scope            | Why Needed                      |
| ---------------- | ------------------------------- |
| `read_products`  | Fetch product data via GraphQL  |
| `read_orders`    | Fetch order data via GraphQL    |
| `write_products` | Receive product update webhooks |
| `write_orders`   | Receive order creation webhooks |

**Why these scopes:**

- `read_*` scopes: Enable data synchronization via API calls
- `write_*` scopes: Required to subscribe to webhooks (Shopify requirement)

**Security note:** We only request minimum required scopes.

### Step 6: Get API Credentials

**For OAuth and GraphQL (Laravel):**

1. In your app, go to **Settings**
2. Scroll to **Credentials**
3. Copy:
   - **API key** (Client ID)
   - **API secret key** (starts with `shpss_`)

**For Webhooks (Node.js):**

1. Go to your development store admin
2. Navigate to: **Settings** → **Notifications**
3. Scroll to **Webhooks** section
4. At the bottom, you'll see: "Your webhooks will be signed with"
5. Copy the secret shown below that text

**⚠️ CRITICAL:** Webhooks use a DIFFERENT secret than OAuth/GraphQL!

---

## Installation

### Clone Repository

```bash
git clone <repository-url>
cd <repository-folder>
```

### Install PHP Dependencies

```bash
cd laravel-api
composer install
```

### Install Node.js Dependencies

```bash
cd webhook-service
npm install
```

### Database Setup

```bash
cd laravel-api

# Create PostgreSQL database
createdb shopify_integration

# Run migrations
php artisan migrate
```

---

## Environment Configuration

### Laravel (.env)

Create `.env` file in `laravel-api` directory (copy from `.env.example`):

```env
APP_NAME="Shopify Integration"
APP_ENV=local
APP_KEY=base64:your_app_key_here
APP_DEBUG=true
APP_URL=http://localhost:8000

# Database
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=shopify_integration
DB_USERNAME=postgres
DB_PASSWORD=your_password

# Shopify Configuration
SHOPIFY_API_KEY=your_api_key_here
SHOPIFY_API_SECRET=shpss_your_oauth_api_secret_here
SHOPIFY_SCOPES=read_products,read_orders,write_products,write_orders
SHOPIFY_API_VERSION=2026-01

# Session
SESSION_DRIVER=database
```

**Important:**

- Use the **API key** (not App ID) for `SHOPIFY_API_KEY`
- Use the **API secret key** from Client credentials for `SHOPIFY_API_SECRET`

### Node.js (.env)

Create `.env` file in `webhook-service` directory (copy from `.env.example`):

```env
PORT=3000
SHOPIFY_API_SECRET=shpss_your_webhook_signing_secret_here

# PostgreSQL (same database as Laravel)
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=shopify_integration
DB_USERNAME=postgres
DB_PASSWORD=your_password
```

**⚠️ CRITICAL:** Use the **Webhook signing secret** from the Webhooks page (NOT the same as Laravel's API secret)!

---

## Running the Services

### PHP/Laravel Service

```bash
cd laravel-api
php artisan serve
```

Server runs on: `http://localhost:8000`

### Node.js Webhook Service

**Open a new terminal** (keep Laravel running in the first terminal)

```bash
cd webhook-service
npm run dev
```

Server runs on: `http://localhost:3000`

**Note:** Both services must run simultaneously in separate terminals.

---

## Shopify Configuration

### OAuth URLs (In App Configuration)

1. Go to Partner Dashboard → Your App → **Configuration**
2. Set **App URL:**

```
   https://your-ngrok-url.ngrok-free.dev/shopify/callback
```

3. Set **Allowed redirection URLs:**

```
   https://your-ngrok-url.ngrok-free.dev/shopify/callback
```

### Webhook Configuration

**For local development, expose webhook service with ngrok:**

```bash
ngrok http 3000
```

Copy the HTTPS URL (e.g., `https://abc123.ngrok-free.dev`)

**Configure webhooks in Shopify Admin:**

1. Go to your development store admin
2. Navigate to: **Settings** → **Notifications**
3. Scroll down to the **Webhooks** section
4. Click **Create webhook**

**Product Update Webhook:**

- Event: `Product update`
- Format: `JSON`
- URL: `https://your-ngrok-url.ngrok-free.dev/webhooks/products/update`
- API version: `2026-01`

**Order Creation Webhook:**

- Event: `Order creation`
- Format: `JSON`
- URL: `https://your-ngrok-url.ngrok-free.dev/webhooks/orders/create`
- API version: `2026-01`

**Save both webhooks.**

---

## Testing the Application

### 1. Test OAuth Flow

**Install app on your development store:**

```
https://your-ngrok-url.ngrok-free.dev/shopify/install?shop=your-store.myshopify.com
```

**Expected flow:**

1. Redirects to Shopify authorization screen
2. User approves scopes
3. Redirects back to callback URL
4. Token is stored (check database: `shops` table)

**Verify in Laravel:**

```bash
cd laravel-api
php artisan tinker
```

```php
>>> \App\Models\Shop::first();
```

### 2. Test Data Sync

**Sync products (PowerShell):**

```powershell
Invoke-RestMethod -Uri "http://localhost:8000/api/sync/products" -Method POST -Headers @{"Content-Type"="application/json"} -Body '{"shop_domain":"your-store.myshopify.com"}'
```

**Expected response:**

```json
{
  "success": true,
  "message": "Successfully synced 20 products",
  "count": 20
}
```

**Sync orders (PowerShell):**

```powershell
Invoke-RestMethod -Uri "http://localhost:8000/api/sync/orders" -Method POST -Headers @{"Content-Type"="application/json"} -Body '{"shop_domain":"your-store.myshopify.com"}'
```

**Verify in database:**

```sql
SELECT COUNT(*) FROM products;
SELECT COUNT(*) FROM orders;
```

### 3. Test Query Endpoints

**Get products (can test in browser):**

```
http://localhost:8000/api/products?page=1&pageSize=10
```

**Filter by vendor:**

```
http://localhost:8000/api/products?vendor=Nike&status=active
```

**Get orders (can test in browser):**

```
http://localhost:8000/api/orders?page=1&pageSize=10
```

**Filter by date (PowerShell):**

```powershell
Invoke-RestMethod -Uri "http://localhost:8000/api/orders?date_from=2026-02-14&date_to=2026-02-16"
```

**Or simply visit in browser:**

```
http://localhost:8000/api/orders?date_from=2026-02-14&date_to=2026-02-16
```

### 4. Test Webhooks

**Trigger product webhook:**

1. Go to your Shopify admin
2. Products → Edit any product
3. Change title or price
4. Click **Save**

**Check Node.js logs:**

```
[WEBHOOK] Received: products/update from your-store.myshopify.com
[HMAC] Verification successful
[WEBHOOK] Stored successfully
```

**Verify in database:**

```sql
SELECT * FROM webhook_events ORDER BY received_at DESC LIMIT 5;
```

**Trigger order webhook:**

1. Go to your store frontend
2. Add product to cart
3. Complete checkout (use Bogus Gateway: card 1, any future expiry)

**Check Node.js logs for `orders/create` webhook.**

### 5. Test Deduplication

**From webhook-service directory:**

```bash
cd webhook-service

# Generate valid HMAC test command
node test-duplicate.js

# Copy the PowerShell command it outputs and run it TWICE
```

**Expected logs:**

```
First request: [WEBHOOK] Stored successfully
Second request: [WEBHOOK] Duplicate ignored
```

**Verify only 1 record in database:**

```sql
SELECT COUNT(*) FROM webhook_events WHERE webhook_id = 'test-duplicate-123';
```

---

## Project Structure

```
.
├── .gitignore (root level - covers both services)
├── README.md
│
├── laravel-api/
│   ├── app/
│   │   ├── Http/
│   │   │   ├── Controllers/
│   │   │   │   ├── Api/
│   │   │   │   │   └── ShopifyApiController.php
│   │   │   │   └── ShopifyOAuthController.php
│   │   │   └── Requests/
│   │   │       ├── SyncProductsRequest.php
│   │   │       ├── SyncOrdersRequest.php
│   │   │       ├── ListProductsRequest.php
│   │   │       └── ListOrdersRequest.php
│   │   ├── Models/
│   │   │   ├── Shop.php
│   │   │   ├── Product.php
│   │   │   ├── Order.php
│   │   │   └── User.php
│   │   ├── Providers/
│   │   └── Services/
│   │       ├── ShopifyGraphQLService.php
│   │       ├── ShopifyOAuthService.php
│   │       └── ShopifySyncService.php
│   ├── config/
│   │   └── shopify.php
│   ├── database/
│   │   └── migrations/
│   ├── routes/
│   │   ├── api.php
│   │   └── web.php
│   ├── .env (not committed)
│   ├── .env.example
│
└── webhook-service/
    ├── config/
    │   └── db.js
    ├── middleware/
    │   ├── middleware.js
    │   └── verifyWebhook.js
    ├── routes/
    │   └── webhooks.js
    ├── .env (not committed)
    ├── .env.example
    ├── .gitignore
    ├── package.json
    ├── package-lock.json
    ├── server.js
    └── test-duplicate.js
```

---

## Security Notes

- Never commit `.env` files
- Root `.gitignore` covers both services
- Webhooks use a DIFFERENT signing secret than OAuth/GraphQL
- Use `.env.example` as template

---

## Troubleshooting

**OAuth fails:**

- Verify `SHOPIFY_API_KEY` and `SHOPIFY_API_SECRET` are correct
- Check redirect URL matches Shopify app configuration
- Ensure shop domain ends with `.myshopify.com`

**Webhook HMAC verification fails:**

- ⚠️ Use the **Webhook signing secret** from Webhooks page (NOT the API secret from Client credentials)
- Restart Node.js service after changing `.env`
- Verify secret has no extra spaces or newlines

**Sync returns 0 products/orders:**

- Verify shop has products/orders in Shopify admin
- Check OAuth token is stored (query `shops` table)
- Ensure `SHOPIFY_API_SECRET` in Laravel is correct

**Database connection fails:**

- Verify PostgreSQL is running
- Check database credentials in `.env`
- Ensure database exists: `createdb shopify_integration`
