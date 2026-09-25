# Test first on new.thegiftboxx.com (recommended)

Try the new store on a test address while **thegiftboxx.com stays on WordPress, untouched**. When you're happy, going live takes about 10 minutes (Part B).

| | Address | Hostinger folder |
|---|---|---|
| Test shop | **https://new.thegiftboxx.com** | `public_html/new` |
| Admin (permanent) | **https://admin.thegiftboxx.com** | `public_html/admin` |
| Private engine | not on the web | `app`, `storage`, `cron` next to `public_html` |
| Current WordPress | https://thegiftboxx.com | `public_html` (leave as is) |

You need the three zips: `1-private-app.zip`, `2-admin.zip` and `3-website.zip`.

---

## Part A: Set up the test site

### A1. PHP version
hPanel → **Websites → thegiftboxx.com → Advanced → PHP Configuration** → **PHP 8.2** or **8.3** → Save.
WordPress runs fine on 8.2 as well.

### A2. Create two subdomains
hPanel → **Domains → Subdomains**:
1. Create `new`. Folder: keep **`public_html/new`**.
2. Create `admin`. Folder: keep **`public_html/admin`**.

Then go to hPanel → **Security → SSL** and check both subdomains show *Active*. This can take 5–15 minutes; click *Install* if needed.

### A3. Create the database
hPanel → **Databases → Management** → new MySQL database:
- name `giftboxx`, user `giftboxx`, password → *Generate* and **save it somewhere**

Note the full names Hostinger shows (e.g. `u123456789_giftboxx`).

### A4. Upload the files (File Manager)
1. Open **`domains/thegiftboxx.com/`**, the folder that *contains* `public_html`.
   Upload **`1-private-app.zip`** → right-click → **Extract** here. You should now see `app`, `cron` and `storage` beside `public_html`.
2. Open **`public_html/new/`** → delete Hostinger's placeholder file, if any → upload **`3-website.zip`** → **Extract** here.
3. Open **`public_html/admin/`** → delete the placeholder, if any → upload **`2-admin.zip`** → **Extract** here.

> Turn on **Show hidden files** in File Manager settings to see the `.htaccess` files. They must be there.

### A5. Run the installer
Open **https://admin.thegiftboxx.com**. The installer fills most fields in for you:
- Website address: **`https://new.thegiftboxx.com`**
- Admin address: **`https://admin.thegiftboxx.com`**
- Uploads folder: should end in **`/public_html/new/uploads`**
- MySQL: the name, user and password from A3; host `localhost`
- Your name, email and admin password
- Keep **"Load my 5 current products"** ticked → **Create my store**

### A6. Keep the test site out of Google
Admin → **Settings → Website** → switch on **"Hide the whole site from Google"** → Save.
(Remember to switch it **off** when you go live.)

### A7. Copy the rest of your WooCommerce data (optional for testing)
Admin → **Import → Full migration from WooCommerce**. Follow the 3 steps on that page to create a read-only API key in your WordPress admin. This brings over categories, all products, reviews, customers and past orders.

### A8. Test everything ✔️
Open **https://new.thegiftboxx.com** on your phone and laptop:

- [ ] Homepage, shop, a category, each product page
- [ ] Pick **Pinewood / Premium Wood** on a product: the background changes
- [ ] Add to cart → cart drawer → checkout (gift message, delivery date)
- [ ] **Settings → Email:** set up SMTP → *Send test email*
- [ ] **Settings → Payments:** PayU and/or Cashfree in **Test/Sandbox** mode → place a test order → check you **and** the customer get emails
- [ ] Turn on **Cash on Delivery** → place an order
- [ ] In admin: open the order → *Packing slip* → *Mark as packed* → add tracking → *Mark as shipped* (customer gets an email)
- [ ] **Track your order** page with the order number + email
- [ ] Contact, Corporate gifting and Design-your-own-box forms (they arrive in admin → Enquiries and in your email)
- [ ] Account sign-up, log in, wishlist
- [ ] Admin → Appearance: change colours/fonts, then *Reset to original*
- [ ] Admin → Box types: tweak Pinewood's colour and watch a product page change
- [ ] Add the admin to your phone's home screen (Safari → Share → *Add to Home Screen*)

Found something to change? Tell me and I'll update the code.

---

## Part B: Go live on thegiftboxx.com

1. **Back up WordPress:** hPanel → **Websites → Backups** → *Generate new backup*.
2. **Run the migration once more** (Admin → Import → *Run again*) so the newest WooCommerce orders and customers are copied.
3. In File Manager, open `public_html/` → create a folder **`_old_wordpress`** → **move all WordPress files and folders** into it (`wp-admin`, `wp-content`, `wp-includes`, `index.php`, all `wp-*.php`, `.htaccess`, `xmlrpc.php`, `license.txt`, `readme.html`).
   ⚠️ **Don't move** `new/` or `admin/`.
4. Open `public_html/new/` → **select everything** inside it (including `.htaccess`, `.user.ini` and the `uploads` folder) → **Move** to `public_html/`.
5. Edit **`domains/thegiftboxx.com/app/config.php`** (File Manager → right-click → Edit) and change two lines:
   ```php
   'site_url' => 'https://thegiftboxx.com',
   'uploads_dir' => '/home/uXXXXXXXX/domains/thegiftboxx.com/public_html/uploads',
   'uploads_url' => 'https://thegiftboxx.com/uploads',
   ```
   Keep the `/home/uXXXXXXXX/…` beginning exactly as it already appears in the file; only remove `/new` from it.
6. Admin → **Settings → Website** → switch **off** "Hide the whole site from Google" → Save.
7. Open **https://thegiftboxx.com**. The new store is live. 🎉
8. hPanel → **Domains → Subdomains** → delete `new`. It's empty now and no longer needed.
9. Finish with **steps 8–10 of `docs/DEPLOYMENT.md`**: the cron job, connecting services in live mode, and submitting the sitemap to Google and Bing.

**Something wrong after going live?** Move the WordPress files back out of `_old_wordpress`. You're back on the old site in a minute while we fix it.
