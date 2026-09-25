# The Gift Boxx

A hand-coded storefront and admin dashboard for **thegiftboxx.com**. It replaces the WordPress + WooCommerce + Elementor setup with a fast, dependency-free PHP app that runs on any Hostinger plan.

- **Shop:** `thegiftboxx.com`. It's premium and responsive, installable as an app, and SEO-ready.
- **Admin:** `admin.thegiftboxx.com`. It has an Apple-style interface, light and dark mode, and works on phone, tablet and desktop.

➡️ **Going live:** [docs/DEPLOYMENT.md](docs/DEPLOYMENT.md)
➡️ **Connecting PayU, Cashfree, Shiprocket, Google, Meta and Pinterest:** [docs/CONNECT-SERVICES.md](docs/CONNECT-SERVICES.md)

## What's included

**Shop**
- Home, shop, category pages, product pages, cart drawer, cart, checkout, order confirmation, order tracking, account, wishlist, search
- Product options (e.g. Box Type) with their own price, photo, stock and sale dates
- **Wood-themed product pages:** choosing Pinewood, Teakwood or Plywood changes the page background. Each product can also use a custom colour.
- Gift message card and preferred delivery date at checkout; guest checkout with an optional account
- Payments: **PayU**, **Cashfree** and **Cash on Delivery**. Coupons, and flat shipping that becomes free above a set amount.
- Enquiry forms: Contact, **Corporate / bulk gifting**, **Design your own box** (ideas sent to you)
- Order confirmation to the customer, new-order alert to you, and shipped / delivered / cancelled updates, plus abandoned-cart reminders
- Blog (hidden until you switch it on), editable pages, 301 redirects, maintenance mode and holiday mode
- SEO: clean URLs identical to the old site, meta tags, Open Graph, Product/FAQ/Breadcrumb/Organization schema, `sitemap.xml`, `robots.txt`, `llms.txt`
- Tracking: GA4, Google Ads conversions, Google Tag Manager, Meta Pixel with Conversions API, Pinterest Tag, and custom code slots
- Product feeds for Google Merchant Center, the Meta catalog and the Pinterest catalog

**Admin**
- Dashboard with sales chart, KPIs, setup checklist, best sellers, low stock and enquiries
- Orders: status flow, packing slip and invoice printing, Shiprocket shipment and AWB in one click, manual tracking, notes, WhatsApp/call shortcuts, CSV export
- Products: drag-and-drop photos, rich text editor, option builder ("Create all combinations"), live Google search and Shopping previews, bulk actions, duplicate
- Categories, box types (with colour and texture preview), brands, reviews, coupons, customers, abandoned carts, enquiries inbox
- Homepage editor, pages, blog, redirects
- Settings: website, appearance (colours, fonts, corners, admin accent), store, payments, shipping, integrations, email
- **Sales channels:** connection status for every service, feed links, per-product Google health check
- **Import:** one-click WooCommerce migration (products, options, photos, reviews, customers, orders) and CSV import
- Team accounts (administrator or shop manager), ⌘K search, unsaved-changes bar, and an iOS-style tab bar on phones

## Folder structure

```
app/            Private code (never web-accessible)
  admin/        Admin controllers
  store/        Storefront controllers
  lib/          Core: database, cart, orders, SEO, email, images, security
  integrations/ PayU, Cashfree, Shiprocket, Meta CAPI, WooCommerce import
  views/        Templates (store, admin, emails)
  schema/       Database tables
  seed/         Products and policies copied from the old site
public/         Shop web root  → public_html
admin/          Admin web root → public_html/admin (admin.thegiftboxx.com)
cron/           Scheduled tasks
storage/        Logs and the local SQLite database
docs/           Guides
```

## Running it locally

Requires PHP 8.2+ with `pdo_sqlite`, `gd`, `curl`, `sodium` and `mbstring`.

```bash
php -S 127.0.0.1:8080 -t public public/index.php   # shop
php -S 127.0.0.1:8081 -t admin  admin/index.php    # admin → open it and choose "SQLite (testing only)"
```

Use the website address `http://127.0.0.1:8080` and admin address `http://127.0.0.1:8081` in the installer.

## Security notes

- Secret keys (payment, Shiprocket, SMTP, Meta token) are encrypted in the database with libsodium, using the key in `app/config.php`.
- Every form is CSRF-protected. Logins are rate-limited. Passwords use `password_hash`.
- Payments are verified server-side: PayU reverse hash plus a verify API call, and Cashfree order status plus a signed webhook. Prices are always recalculated on the server.
- Uploaded images are type-checked and re-encoded. The uploads folder can't run scripts.
- `app/`, `storage/` and `cron/` sit outside the web root, and each also has a deny-all `.htaccess`.
