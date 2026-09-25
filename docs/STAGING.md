# Testing the new website on new.thegiftboxx.com (Hostinger, click by click)

Your current website (thegiftboxx.com on WordPress) **stays exactly as it is** during all of Part A.

**You will need**
- Your Hostinger login
- The 3 zip files: `1-private-app.zip`, `2-admin.zip`, `3-website.zip` (download them to your computer; don't unzip them)
- About 45 minutes

**About the "A record points outside Hostinger" message**
Your main site runs through **Hostinger's CDN**, so its DNS points at CDN addresses instead of directly at the hosting server. Hostinger shows that warning whenever that's the case. It's harmless: `new.thegiftboxx.com` and `admin.thegiftboxx.com` already resolve to Hostinger servers. Click **Continue / Create anyway**. If you've already created them, move on.

---

## PART A: Set up the test site

### Step 1: Open your website's dashboard
1. Go to **hpanel.hostinger.com** and log in.
2. Click **Websites** in the left menu.
3. Next to **thegiftboxx.com**, click **Dashboard** (or **Manage**).

From now on, "the left menu" means the menu on this website dashboard.

### Step 2: Set the PHP version
1. Left menu → **Advanced** → **PHP Configuration**.
2. On the **PHP version** tab, select **8.3** (or 8.2) → **Update**.
3. Click the **PHP extensions** tab. Make sure these are ticked: `curl`, `gd`, `mbstring`, `pdo_mysql`, `sodium`, `fileinfo`, `dom`. They normally already are. If you change anything, click **Save**.

### Step 3: Create the two subdomains
*(Skip this if you already created them.)*
1. Left menu → **Domains** → **Subdomains**.
2. In **Create a new subdomain**, type `new`. Leave "Custom folder" **unticked**. Hostinger will use `public_html/new`. Click **Create**.
   - If you see the A-record warning → click **Continue / Create anyway**.
3. Repeat with `admin`. It will use `public_html/admin`.
4. Both now appear under **List of current subdomains**.

### Step 4: Turn on SSL (the padlock) for both
1. Left menu → **Security** → **SSL**.
2. Find `new.thegiftboxx.com` and `admin.thegiftboxx.com`. If either says anything other than **Active**, click **Install SSL** next to it.
3. SSL can take 10–30 minutes to become active. You can do steps 5–7 while you wait.

### Step 5: Create the database
1. Left menu → **Databases** → **Management**.
2. In **Create a new MySQL database and database user**:
   - **MySQL database name:** type `giftboxx`
   - **MySQL username:** type `giftboxx`
   - **Password:** click **Generate**, then click the eye icon and **copy the password into a note on your phone/PC**
3. Click **Create**.
4. In the list below, copy the **full** database name and username. Hostinger adds a prefix, e.g. `u123456789_giftboxx`.

📝 Write down: database name, username, password.

### Step 6: Upload the private app files
1. Left menu → **Files** → **File Manager**. It opens inside `public_html`.
2. Go **one level up**: click `thegiftboxx.com` in the path bar at the top (the path looks like `domains › thegiftboxx.com › public_html`), or click the ⬆ "up" arrow.
   ✅ You're in the right place when you see the **`public_html`** folder in the list.
3. Click the **Upload** icon (arrow pointing up) → **File** → choose **`1-private-app.zip`**. Wait for 100%.
4. Right-click `1-private-app.zip` → **Extract** → leave the folder name empty or `.` (meaning "here") → **Extract**.
5. ✅ You should now see **`app`**, **`cron`** and **`storage`** next to `public_html`.
6. Right-click `1-private-app.zip` → **Delete** (it's no longer needed).

> ⚠️ These three folders must **not** go inside `public_html`. They hold private code and passwords.

### Step 7: Upload the test website
1. Double-click **`public_html`** → double-click **`new`**.
2. If there's a file like `default.php` or `index.html` inside, delete it.
3. **Upload** → **File** → **`3-website.zip`** → then right-click it → **Extract** here → then delete the zip.
4. ✅ Inside `public_html/new` you should now see `assets`, `uploads`, `index.php`, `manifest.webmanifest`, `sw.js` and `favicon.ico`.
5. Click the **gear icon (Settings)** in File Manager → tick **Show hidden files**. You should also see **`.htaccess`** and **`.user.ini`**.

### Step 8: Upload the admin
1. Go back to `public_html` → double-click **`admin`**.
2. Delete any placeholder file inside.
3. **Upload** → **`2-admin.zip`** → right-click → **Extract** here → delete the zip.
4. ✅ Inside `public_html/admin` you should see `assets`, `index.php`, `.htaccess` and `.user.ini`.

### Step 9: Run the installer
1. Open a new browser tab → go to **https://admin.thegiftboxx.com**
   - If you see a security warning, SSL isn't ready yet. Wait 15 minutes and try again.
2. You'll see **"Set up The Gift Boxx"**. Check or fill in:
   - **Website address:** `https://new.thegiftboxx.com`
   - **Admin address:** `https://admin.thegiftboxx.com`
   - **Uploads folder:** leave as it is (it ends with `/public_html/new/uploads`)
   - **Database:** select **MySQL (Hostinger)**. Enter the database name, user and password from Step 5. Host: `localhost`
   - **Your admin account:** your name, email and a password of 10+ characters (write it down)
   - Keep **"Load my 5 current products…"** ticked
3. Click **Create my store**. It takes 1–2 minutes because it downloads your product photos. Don't close the tab.
4. ✅ You land on the **Dashboard** with "Welcome! Your store is ready".

### Step 10: Hide the test site from Google
1. In the admin, left menu → **Settings** → **Website**.
2. Switch **ON** "Hide the whole site from Google (only for testing!)" → click **Save** at the bottom.

### Step 11: Look at your new shop
Open **https://new.thegiftboxx.com** on your laptop and phone. 🎉

### Step 12: Turn on emails (so order emails work while you test)
**First, create a mailbox in Hostinger:**
1. Go back to hPanel → left menu **Emails** → **Email Accounts**.
2. Click **Create email account** → name it `orders` → set a password → **Create**.

**Then connect it in the admin:**
3. Admin → **Settings → Email**:
   - SMTP host `smtp.hostinger.com`, port `465`, encryption `SSL`
   - SMTP username `orders@thegiftboxx.com`, SMTP password = that mailbox's password
4. Click **Save**, then **Send test email** at the top. Check your inbox and spam folder.
5. Admin → **Settings → Store** → **Store email**: the address where you want **new-order alerts** → **Save**.

### Step 13: Turn on a way to pay (for testing)
The easiest option is **Settings → Payments** → switch on **Cash on Delivery** → **Save**.
To test PayU or Cashfree too, see `docs/CONNECT-SERVICES.md` and keep them in **Test / Sandbox** mode.

### Step 14: Test checklist ✔️
- [ ] Homepage, Shop, a category, every product page
- [ ] On a product, pick **Pinewood**, then **Premium Wood**. The page background and price change.
- [ ] Add to cart → cart drawer opens → Checkout → add a gift message and delivery date → place a **COD** order
- [ ] You receive the "New order" email and the customer email receives the confirmation
- [ ] Admin → **Orders** → open the order → **Packing slip** → **Mark as packed** → under Shipping, "Enter tracking manually" → **Save tracking** (customer gets a "shipped" email)
- [ ] Shop → footer → **Track your order** with the order number and email
- [ ] Send the Contact, Corporate gifting and Design-your-own-box forms → they appear in Admin → **Enquiries**
- [ ] Create an account, log in, add to wishlist
- [ ] Admin → **Settings → Appearance** → change a colour → **Save** → check the shop → **Reset to original**
- [ ] On your phone, open the admin → Share → **Add to Home Screen**

Anything you'd like changed? Send me a screenshot and a note.

---

## PART B: Go live on thegiftboxx.com (after testing, about 15 minutes)

1. **Copy the latest WordPress data:** Admin → **Import** → follow the 3 steps on that page (create a read-only REST API key in WordPress → paste it → **Start migration**). Wait for "Finished".
2. **Back up WordPress:** hPanel → left menu **Files** → **Backups** → **Generate new backup**. Wait until it's done.
3. **Move WordPress aside:** File Manager → open `public_html` → click **New folder** → name it `_old_wordpress`.
   Select everything **except** the `new`, `admin` and `_old_wordpress` folders (tick the boxes: `wp-admin`, `wp-content`, `wp-includes`, `index.php`, all `wp-…php` files, `.htaccess`, `xmlrpc.php`, `license.txt`, `readme.html` …) → **Move** → choose `public_html/_old_wordpress` → **Move**.
4. **Move the new shop in:** open `public_html/new` → select **all** (including `.htaccess`, `.user.ini` and `uploads`) → **Move** → choose `public_html` → **Move**.
5. **Point the settings at the real domain:** go up to `domains/thegiftboxx.com/app` → right-click **`config.php`** → **Edit**. In these 3 lines, **delete `new.` and `/new`**:
   ```
   'site_url' => 'https://new.thegiftboxx.com',              →  'https://thegiftboxx.com'
   'uploads_dir' => '…/public_html/new/uploads',              →  '…/public_html/uploads'
   'uploads_url' => 'https://new.thegiftboxx.com/uploads',    →  'https://thegiftboxx.com/uploads'
   ```
   Click **Save**.
6. **Clear Hostinger's cache:** hPanel → website dashboard → **Performance** → **CDN** → **Purge all cache**. (Also look for a **Clear cache** button on the dashboard home.)
7. **Let Google in again:** Admin → **Settings → Website** → switch **OFF** "Hide the whole site from Google" → **Save**.
8. Open **https://thegiftboxx.com**. 🎉 Place one more test order.
9. **Schedule reminder emails:** hPanel → **Advanced** → **Cron Jobs** → *Custom* → paste the command shown at the bottom of Admin → **Settings → Email** → set it to run **every 30 minutes** → **Save**.
10. **Tell Google:** Search Console → **Sitemaps** → submit `https://thegiftboxx.com/sitemap.xml`.
11. After a week with no problems: delete the `new` subdomain (hPanel → Domains → Subdomains), the `_old_wordpress` folder and the old WordPress database.

**Something wrong after going live?** Move the files from `_old_wordpress` back into `public_html` (and move the new shop files back into `new`). You're back on WordPress in 2 minutes.
