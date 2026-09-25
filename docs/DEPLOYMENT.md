# Putting the new Gift Boxx website live (Hostinger)

This guide moves thegiftboxx.com from WordPress to the new hand-coded store **without downtime and without losing Google rankings**. Take it one step at a time. Everything happens in **Hostinger hPanel**; you don't need to touch GoDaddy, because your domain already points to Hostinger.

**Time needed:** about 1 hour.
**Keep WordPress running** until step 7 — the new admin copies your products, customers and orders from it.

---

## What goes where

The download contains three zip files:

| Zip | What it is | Where it goes on Hostinger |
|---|---|---|
| `1-private-app.zip` | The engine: code, settings, database setup (private) | `domains/thegiftboxx.com/` (next to `public_html`, **not inside it**) |
| `2-admin.zip` | The admin dashboard | `domains/thegiftboxx.com/public_html/admin/` |
| `3-website.zip` | The public shop | `domains/thegiftboxx.com/public_html/` (in step 7) |

When you're finished, your File Manager will look like this:

```
domains/thegiftboxx.com/
├── app/              ← private (code, config.php)
├── storage/          ← private (logs)
├── cron/             ← private (scheduled tasks)
└── public_html/      ← the website
    ├── index.php, assets/, uploads/ …
    └── admin/        ← admin.thegiftboxx.com
```

---

## Step 1: Set PHP to version 8.2 or newer

hPanel → **Websites → thegiftboxx.com → Advanced → PHP Configuration** → choose **PHP 8.2** or **8.3** → Save.
On the **PHP extensions** tab, make sure these are ticked (they usually already are): `pdo_mysql`, `curl`, `gd`, `mbstring`, `sodium`, `fileinfo`, `dom`.

## Step 2: Create the database

hPanel → **Databases → Management** → *Create a new MySQL database*:

- Database name: `giftboxx` (Hostinger adds a prefix, e.g. `u123456789_giftboxx`)
- Username: `giftboxx` (becomes `u123456789_giftboxx`)
- Password: press *Generate*, then **copy it somewhere safe**

Write down the full **database name**, **username** and **password**. You'll need them in step 5.

## Step 3: Create the admin subdomain

hPanel → **Domains → Subdomains** → create `admin` for `thegiftboxx.com`.
If it asks for a folder, keep the suggested `public_html/admin`.

Then go to hPanel → **Security → SSL** and make sure `admin.thegiftboxx.com` has SSL. Hostinger usually adds it automatically within a few minutes; if not, click *Install*.

## Step 4: Upload the private app and the admin

1. hPanel → **Files → File Manager** → open `domains/thegiftboxx.com/`, the folder that **contains** `public_html`.
2. Upload `1-private-app.zip` here → right-click → **Extract** → extract into the current folder. You should now see `app`, `storage` and `cron` next to `public_html`.
3. Open `public_html/admin/`. Delete any default file Hostinger put there, such as `default.php`.
4. Upload `2-admin.zip` into `public_html/admin/` → **Extract** here.

> Can't see `.htaccess` files? In File Manager settings, turn on *Show hidden files*. They must be uploaded too; the zips include them.

## Step 5: Run the installer

Open **https://admin.thegiftboxx.com** in your browser. You'll see "Set up The Gift Boxx".

- **Website address:** `https://thegiftboxx.com`
- **Admin address:** `https://admin.thegiftboxx.com`
- **Uploads folder:** keep the suggested value (it ends in `/public_html/uploads`)
- **Database:** choose MySQL and enter the name, user and password from step 2. Host: `localhost`
- **Your admin account:** your name, email and a strong password (10+ characters)
- Keep **"Load my 5 current products"** ticked

Press **Create my store** and wait about a minute while it downloads the photos. You'll land on your new dashboard.

> If it says the config file couldn't be saved, it shows you the exact text to paste into `app/config.php` using File Manager's *New file*. Do that, then reload.

## Step 6: Bring over everything else from WooCommerce

In the new admin → **Import → Full migration from WooCommerce**:

1. In your **old** WordPress admin (`thegiftboxx.com/wp-admin`): **WooCommerce → Settings → Advanced → REST API → Add key** → Description `Migration`, Permissions **Read** → *Generate API key*.
2. Copy the *Consumer key* and *Consumer secret* into the Import page → **Start migration**. Keep the tab open until it says *Finished*.

This copies categories (with their exact old URLs), all products and options, photos, reviews, customers and past orders. Running it twice is safe; nothing gets duplicated.

Afterwards, check **Products**. Open one product and press **View**: it opens at thegiftboxx.com, which is still WordPress until step 7, so the product page will look like the old site for now. That's expected.

## Step 7: Switch the website over (about 5 minutes)

1. **Back up WordPress first:** hPanel → **Websites → Backups** → *Generate new backup* (or download the `public_html` folder).
2. In File Manager, open `public_html/`. Create a folder called `_old_wordpress`.
3. Select **every WordPress file and folder** (`wp-admin`, `wp-content`, `wp-includes`, `index.php`, `wp-*.php`, `.htaccess`, `xmlrpc.php`, `license.txt`, `readme.html` and so on) and **Move** them into `_old_wordpress`.
   **Do not move** `admin/` or `uploads/`; those belong to the new site.
4. Upload `3-website.zip` into `public_html/` → **Extract** here.
5. Visit **https://thegiftboxx.com**. You should see the new store.

If anything looks wrong, move the WordPress files back out of `_old_wordpress` and you're back where you started.

Once you're happy (give it a week), delete `_old_wordpress` and the old WordPress database.

## Step 8: Schedule the reminder emails (cron)

hPanel → **Advanced → Cron Jobs** → Custom:

- **Command:** copy it from the admin: **Settings → Email** (bottom of the page). It looks like
  `curl -s "https://thegiftboxx.com/cron/run?token=…" > /dev/null`
- **Schedule:** every 30 minutes (`*/30 * * * *`)

This sends abandoned-cart reminders and double-checks Cashfree payments.

## Step 9: Connect your services

Work through the **"Finish setting up your store"** checklist on the dashboard. Each item links to the right settings page, and `docs/CONNECT-SERVICES.md` explains where to find every key:

- **Email (do this first):** so customers get order confirmations and you get new-order alerts
- **PayU and/or Cashfree**
- **Shiprocket**
- **Google Analytics, Search Console, Google Ads, Merchant Center**
- **Meta Pixel + catalog, Pinterest**

Then place a real **₹1 test order**: create a product priced ₹1, set it to visible, buy it, and refund it from the payment dashboard.

## Step 10: Tell Google

1. Google Search Console → your property → **Sitemaps** → submit `https://thegiftboxx.com/sitemap.xml`.
2. Bing Webmaster Tools → add the site → submit the same sitemap. ChatGPT search relies on Bing.
3. Merchant Center → Products → Feeds → add `https://thegiftboxx.com/feeds/google.xml` as a *scheduled fetch* (daily).

All your old product and category links stay the same, so rankings carry over.

---

## Handy facts

- **Admin:** https://admin.thegiftboxx.com. Add it to your phone's home screen for an app-like experience.
- **Private preview while in maintenance mode:** Admin → Settings → Website → *Private preview link*.
- **Logs if something breaks:** `storage/logs/app.log` and `storage/logs/php-error.log`.
- **Backups:** Hostinger backs up daily. Before big changes, also export the database (hPanel → Databases → phpMyAdmin → Export).
- **Changing the admin address or domain:** edit `site_url` / `admin_url` in `app/config.php`.

## Troubleshooting

| Problem | Fix |
|---|---|
| "App folder not found" | `app/` must sit next to `public_html`, not inside it. Or create `public_html/admin/app-path.php` containing `<?php return '/home/uXXXX/domains/thegiftboxx.com/app';` |
| Blank page or 500 error | Check PHP is 8.2+ (step 1), then read `storage/logs/php-error.log` |
| Photos don't upload | Make `public_html/uploads` writable (permissions 755) |
| Emails don't arrive | Settings → Email → *Send test email*. Use your Hostinger email address and password (smtp.hostinger.com, port 465, SSL) |
| Payment page shows an error | Check the keys and the Test/Live switch in Settings → Payments, then use *Test* |
| AI tools still can't read the site | That's Hostinger's server rate limit, not the website. See the Cloudflare notes in `docs/CONNECT-SERVICES.md` |
