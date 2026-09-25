# Connecting your services

Every key goes into **admin.thegiftboxx.com → Settings**. Passwords and secret keys are stored **encrypted**. Once saved, they show only as `••••••••1234`. Never paste secret keys into chat, email or WhatsApp.

After saving, use the **Test** buttons at the top of each settings page. The **Sales channels** page shows everything that's connected at a glance.

---

## Order emails (SMTP): do this first

This lets the store email customers their confirmations and send you a "New order" alert.

1. hPanel → **Emails** → create a mailbox, e.g. `orders@thegiftboxx.com` (or use `support@thegiftboxx.com`).
2. Admin → **Settings → Email**:
   - SMTP host `smtp.hostinger.com`, port `465`, encryption `SSL`
   - SMTP username: the full email address. SMTP password: that mailbox's password
3. **Settings → Store → Store email:** the address that should receive new-order alerts.
4. Press **Send test email**.

Tip: in hPanel → Emails → DNS settings, make sure SPF, DKIM and DMARC show green. This keeps your emails out of spam.

## PayU

1. PayU Dashboard → **Developers → API Keys** (switch to *Test mode* first to try it out).
2. Admin → **Settings → Payments** → turn on PayU, choose *Test*, and paste the **Merchant Key** and **Salt (v1)**.
3. Place a test order with PayU's test card. When it works, switch the dashboard to Live, paste the **live** key and salt, and set the mode to *Live*.

No webhook is needed; the store verifies each payment with PayU's servers.

## Cashfree

1. Cashfree Merchant Dashboard → **Developers → API Keys** → *Payment Gateway*. Copy the **App ID** and **Secret Key** for Sandbox or Production.
2. Admin → **Settings → Payments** → turn on Cashfree, pick Sandbox or Production, and paste the keys.
3. Cashfree → **Developers → Webhooks** → add `https://thegiftboxx.com/payment/cashfree/webhook` (event: *Payment success*). This confirms payments even if a customer closes the browser early.
4. Cashfree → **Developers → Whitelisting** → add `thegiftboxx.com` if it asks for domain whitelisting.

You can offer both gateways at once; customers choose at checkout. **Cash on Delivery** is on the same page, with an optional extra fee and a maximum order value.

## Shiprocket

1. Shiprocket → **Settings → API → Configure → Create an API User**. Use a **different email** from your main login (Shiprocket requires this). Set a password.
2. Admin → **Settings → Shipping** → turn on Shiprocket and enter the API user's email and password.
3. **Pickup location name:** type it exactly as it appears in Shiprocket → Settings → Pickup Addresses (often `Primary`). Also enter the pickup pincode.
4. Press **Test Shiprocket**.

On any order, press **Create Shiprocket shipment**. It sends the order, assigns a courier and saves the AWB. The customer gets a tracking email when you mark it shipped. Turn on *Send paid orders to Shiprocket automatically* if you want this to happen by itself.

## Google Analytics 4

Analytics → **Admin → Data streams** → your web stream → copy the **Measurement ID** (`G-XXXXXXX`) → Settings → Google, Meta & more.
The store sends view item, add to cart, begin checkout and purchase (with revenue) automatically.

## Google Search Console

Search Console → Add property → **URL prefix** `https://thegiftboxx.com` → *HTML tag* method → copy only the `content="…"` value → paste it into **Google Search Console verification code** → Save → press *Verify* in Search Console. Then submit `https://thegiftboxx.com/sitemap.xml` under **Sitemaps**.

## Google Ads conversion tracking

Google Ads → **Goals → Conversions → New conversion action → Website → Purchase** → *Use Google tag manually*. You'll see `AW-123456789/AbCdEfGh`:

- The part before `/` goes in **Google Ads Conversion ID**
- The part after `/` goes in **Purchase conversion label**

Each purchase is reported with its order value.

## Google Merchant Center (free listings & Shopping ads)

1. Merchant Center → verify and claim `thegiftboxx.com`. The Search Console verification above usually covers this.
2. **Products → Feeds → Add primary feed** → India, English → *Scheduled fetch* → URL `https://thegiftboxx.com/feeds/google.xml` → daily.
3. Paste your **Merchant Center ID** into settings so the Sales channels page shows it as connected.
4. Link Merchant Center to Google Ads (Settings → Linked accounts) to run Shopping ads.

Check **Sales channels** in the admin: every product is checked for photos, price, description length, GTIN and so on, with a preview of its Shopping card.

## Meta (Facebook & Instagram)

- **Pixel:** Events Manager → your dataset/pixel → copy the **Pixel ID** (numbers only).
- **Conversions API token:** Events Manager → pixel → **Settings → Conversions API → Generate access token**. Purchases are then also sent from the server, which ad-blockers can't stop. Both are de-duplicated automatically.
- **Domain verification:** Business Settings → **Brand safety → Domains** → add `thegiftboxx.com` → *Meta-tag* → paste the `content` value.
- **Catalog (Instagram & Facebook Shop):** Commerce Manager → Catalogue → **Data sources → Data feed → Scheduled feed** → `https://thegiftboxx.com/feeds/meta.xml` → daily.

## Pinterest

- **Tag:** Pinterest Business → **Ads → Conversions → Pinterest Tag** → copy the **Tag ID**.
- **Verify website:** Settings → **Claimed accounts → Claim website** → *Add HTML tag* → paste the `content` value into **Pinterest site verification code**.
- **Catalog:** Pinterest Business → **Catalogues → Add data source** → `https://thegiftboxx.com/feeds/pinterest.xml`.

## Bing Webmaster (Bing & ChatGPT search)

bing.com/webmasters → add site. You can import it from Google Search Console in one click, or use the meta-tag method and paste the code into **Bing Webmaster verification code**. Then submit the sitemap.

## WhatsApp button

Settings → Google, Meta & more → **WhatsApp number**, digits only with country code (e.g. `917710970512`), plus a default greeting. It powers the floating button and the "Chat with us" link on product pages.

## Anything else (Hotjar, Clarity, chat widgets …)

Paste their script into **Custom code in `<head>`** or **Custom code before `</body>`**. This replaces the old *Header Footer Code Manager* plugin.

---

## If AI tools still can't read the site

Hostinger's shared servers rate-limit some AI crawlers (GPTBot gets "429 Too Many Requests"). The new site itself does nothing to block them: `robots.txt` allows them, and `/llms.txt` gives AI tools a clean summary of your shop. To get past Hostinger's limiter, put **Cloudflare (free)** in front of the site:

1. Add the site at cloudflare.com (free plan). Then at **GoDaddy → your domain → DNS → Nameservers → Change**, enter Cloudflare's two nameservers.
2. Cloudflare → **SSL/TLS** → *Full (strict)*.
3. Cloudflare → **Security → Bots** → turn **off** "Block AI bots" and "AI Labyrinth".
4. **Caching → Cache Rules:**
   - *Bypass cache* for `/cart`, `/checkout`, `/my-account`, `/order`, `/pay`, `/payment` and `/cron`, and for anything on `admin.thegiftboxx.com`
   - *Cache everything else* (Edge TTL 2 hours)
