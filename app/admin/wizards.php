<?php
declare(strict_types=1);

/*
 * Guided setup: each wizard walks through one service a step at a time.
 * A step can show instructions, a button to the right page of that service,
 * values to copy, the settings fields to fill (saved when you press Next)
 * and a connection test.
 */

function setup_wizards(): array
{
    $site = site_url();
    $host = parse_url($site, PHP_URL_HOST);
    $live = preg_replace('/^(new|staging|test)\./', '', (string) $host);
    $a = fn(string $url, string $label) => '<a href="' . e($url) . '" target="_blank" rel="noopener">' . e($label) . '</a>';
    return [
        'email' => [
            'title' => 'Order emails', 'icon' => 'mail', 'time' => '5 min', 'area' => 'Essentials',
            'intro' => 'Send order confirmations to customers and new-order alerts to you from your own address, like orders@' . $live . '.',
            'done' => fn() => (bool) setting('smtp_pass'),
            'steps' => [
                ['title' => 'Create a mailbox in Hostinger', 'link' => ['Open Hostinger Emails', 'https://hpanel.hostinger.com/emails'],
                    'text' => '<ol><li>Open Hostinger with the button below and pick <b>' . e($live) . '</b>.</li><li>Click <b>Create email account</b>.</li><li>Name it <b>orders</b> (or hello), choose a strong password and save it somewhere safe.</li></ol><p>Hostinger sets up the SPF and DKIM records for you, which keeps your emails out of spam.</p>'],
                ['title' => 'Enter the mailbox details', 'text' => '<p>For a Hostinger mailbox the server is <b>smtp.hostinger.com</b>, port <b>465</b>, SSL. Use the full email address as the username.</p>',
                    'fields' => ['email' => ['smtp_host', 'smtp_port', 'smtp_secure', 'smtp_user', 'smtp_pass', 'mail_from_name']]],
                ['title' => 'Send yourself a test', 'text' => '<p>We’ll send a test email to your admin address. If it lands in spam, mark it “Not spam” once.</p>', 'test' => '/settings-test/email'],
            ],
        ],
        'cron' => [
            'title' => 'Scheduled tasks', 'icon' => 'clock', 'time' => '3 min', 'area' => 'Essentials',
            'intro' => 'Lets the store send abandoned-cart reminders and double-check payments in the background.',
            'done' => fn() => (bool) setting('cron_last_run', ''),
            'steps' => [
                ['title' => 'Open Cron Jobs in Hostinger', 'link' => ['Open Hostinger', 'https://hpanel.hostinger.com/'],
                    'text' => '<ol><li>In hPanel open <b>Websites → Dashboard</b> for your site.</li><li>In the left menu open <b>Advanced → Cron Jobs</b>.</li><li>Choose <b>Custom</b>.</li></ol>'],
                ['title' => 'Add the job', 'copy' => ['Command' => 'curl -s "' . site_url('cron/run?token=' . cron_token()) . '" > /dev/null'],
                    'text' => '<ol><li>Paste the command below into the command box.</li><li>Set it to run <b>every 30 minutes</b> (minute: <code>*/30</code>, every other field <code>*</code>).</li><li>Click <b>Save</b>.</li></ol><p>“Last run” in Settings → Emails changes from “never” within half an hour.</p>'],
            ],
        ],
        'payu' => [
            'title' => 'PayU', 'icon' => 'card', 'time' => '10 min', 'area' => 'Payments',
            'intro' => 'Accept UPI, cards, net banking and wallets through PayU.',
            'done' => fn() => setting_on('payu_enabled') && setting('payu_key') && setting('payu_salt'),
            'steps' => [
                ['title' => 'Log in to PayU', 'link' => ['Open PayU dashboard', 'https://dashboard.payu.in/'],
                    'text' => '<p>Log in with the account your business is registered on. Live keys only work once PayU has approved your KYC; test keys work straight away.</p><p>Use the switch at the top of the PayU dashboard to choose <b>Test</b> or <b>Live</b> mode — the keys are different for each.</p>'],
                ['title' => 'Copy your Key and Salt', 'text' => '<ol><li>In the left menu open <b>Developers → API Keys</b> (older dashboards: <b>Settings → Key Salt Details</b>).</li><li>Copy the <b>Merchant Key</b>.</li><li>Copy the <b>Salt</b> (the 32-character “Salt v1”).</li></ol><p>Paste them in the next step. They’re stored encrypted and never shown again.</p>'],
                ['title' => 'Paste them here', 'fields' => ['payments' => ['payu_mode', 'payu_key', 'payu_salt', 'payu_title', 'payu_enabled']],
                    'text' => '<p>Make sure the mode matches the keys you copied. Turn on <b>Enable PayU</b> to show it at checkout.</p>'],
                ['title' => 'Check the connection', 'test' => '/settings-test/payu', 'text' => '<p>PayU needs no webhook — payments are verified with PayU’s servers when the customer returns.</p>'],
                ['title' => 'Place a test order', 'text' => '<p>Open your shop, add a box to the cart and pay. In test mode use PayU’s test card <b>5123 4567 8901 2346</b>, expiry any future date, CVV <b>123</b>, OTP <b>123456</b>.</p><p>When it works, come back, switch the mode to <b>Live</b> with your live keys and place one small real order.</p>', 'link' => ['Open the shop', $site . '/shop/']],
            ],
        ],
        'cashfree' => [
            'title' => 'Cashfree', 'icon' => 'card', 'time' => '10 min', 'area' => 'Payments',
            'intro' => 'Accept UPI, cards, net banking and pay-later through Cashfree.',
            'done' => fn() => setting_on('cashfree_enabled') && setting('cashfree_app_id') && setting('cashfree_secret'),
            'steps' => [
                ['title' => 'Log in to Cashfree', 'link' => ['Open Cashfree', 'https://merchant.cashfree.com/'],
                    'text' => '<p>Log in and open <b>Payment Gateway</b>. Use the <b>Test / Production</b> switch at the top — the keys are different for each.</p>'],
                ['title' => 'Generate API keys', 'text' => '<ol><li>Open <b>Developers → API Keys</b>.</li><li>Click <b>Generate API Keys</b> (or <b>View</b>).</li><li>Copy the <b>App ID</b> and the <b>Secret Key</b>.</li></ol>'],
                ['title' => 'Paste them here', 'fields' => ['payments' => ['cashfree_mode', 'cashfree_app_id', 'cashfree_secret', 'cashfree_title', 'cashfree_enabled']]],
                ['title' => 'Add the webhook and whitelist your site', 'copy' => ['Webhook URL' => site_url('payment/cashfree/webhook'), 'Your website' => 'https://' . $live],
                    'text' => '<ol><li><b>Developers → Webhooks → Add Webhook Endpoint</b>: paste the webhook URL, select the <b>Payment</b> events and API version <b>2023-08-01</b>.</li><li><b>Developers → Whitelisting → Add domain</b>: add your website (Cashfree only allows live payments from approved domains; approval takes a few hours).</li></ol>'],
                ['title' => 'Check the connection', 'test' => '/settings-test/cashfree', 'text' => '<p>Then place a test order from the shop. Cashfree test mode shows a page where you can mark the payment as successful.</p>'],
            ],
        ],
        'shiprocket' => [
            'title' => 'Shiprocket', 'icon' => 'truck', 'time' => '8 min', 'area' => 'Shipping',
            'intro' => 'Send orders to Shiprocket in one click, get AWB numbers and live tracking on the Track order page.',
            'done' => fn() => setting_on('shiprocket_enabled') && setting('shiprocket_password'),
            'steps' => [
                ['title' => 'Create an API user', 'link' => ['Open Shiprocket', 'https://app.shiprocket.in/'],
                    'text' => '<ol><li>In Shiprocket open <b>Settings → API → Configure</b>.</li><li>Click <b>Create an API User</b>.</li><li>Use an email that is <b>different</b> from your Shiprocket login (e.g. api@' . e($live) . ') and set a password.</li></ol>'],
                ['title' => 'Find your pickup location name', 'text' => '<p>Open <b>Settings → Pickup Addresses</b>. Note the nickname of your pickup address exactly as written (for example <b>Primary</b>) and its pincode.</p>'],
                ['title' => 'Enter the details', 'fields' => ['shipping' => ['shiprocket_email', 'shiprocket_password', 'shiprocket_pickup', 'shiprocket_pickup_pincode', 'shiprocket_enabled', 'shiprocket_auto']],
                    'text' => '<p>Leave <b>Send paid orders automatically</b> off if you prefer to check each order first and press “Ship with Shiprocket” yourself.</p>'],
                ['title' => 'Check the connection', 'test' => '/settings-test/shiprocket', 'text' => '<p>Also set each product’s weight and box size (Products → a product → Shipping) so couriers quote the right price.</p>'],
            ],
        ],
        'seo' => [
            'title' => 'SEO basics', 'icon' => 'sparkle', 'time' => '10 min', 'area' => 'Google',
            'intro' => 'How your homepage appears on Google and when shared, plus Bing. Each product, page and category also has its own SEO panel with a live score.',
            'done' => fn() => (bool) setting('seo_home_title') && !setting_on('noindex_site'),
            'steps' => [
                ['title' => 'Homepage title and description', 'fields' => ['website' => ['seo_home_title', 'seo_home_description', 'seo_separator']],
                    'text' => '<p>Write it the way you’d describe the shop to a friend. Keep the title under 60 characters and the description around 150.</p>'],
                ['title' => 'Share image and visibility', 'fields' => ['website' => ['og_image', 'noindex_site']],
                    'text' => '<p>The share image shows when someone sends your link on WhatsApp or Instagram. Keep <b>Hide the whole site from Google</b> off once you’re live.</p>'],
                ['title' => 'Bing (optional)', 'link' => ['Open Bing Webmaster Tools', 'https://www.bing.com/webmasters'],
                    'text' => '<p>Easiest: sign in and choose <b>Import from Google Search Console</b> — no code needed. Otherwise add your site, pick <b>HTML Meta Tag</b> and paste only the <code>content</code> value below.</p>',
                    'fields' => ['integrations' => ['bing_verification']]],
                ['title' => 'Optimise each product', 'link' => ['Go to products', '/products'],
                    'text' => '<p>Open any product, page, blog post or category and scroll to the <b>SEO</b> panel. Set a focus keyword and follow the checklist until the score turns green. Aim for honest, specific wording — how the box looks, what’s inside, who it suits.</p>'],
            ],
        ],
        'gsc' => [
            'title' => 'Google Search Console', 'icon' => 'search', 'time' => '5 min', 'area' => 'Google',
            'intro' => 'See which searches bring people to your site and tell Google about new products quickly. Do this on your live address.',
            'done' => fn() => (bool) setting('gsc_verification'),
            'steps' => [
                ['title' => 'Add your site', 'link' => ['Open Search Console', 'https://search.google.com/search-console/welcome'],
                    'text' => '<ol><li>Choose <b>URL prefix</b> and enter <b>' . e($site) . '/</b></li><li>Pick the <b>HTML tag</b> method.</li><li>Copy only the long code inside <code>content="…"</code>.</li></ol><p>Already verified your domain earlier (for example through Google Analytics or the old WordPress site)? Then you can skip to the sitemap step.</p>'],
                ['title' => 'Paste the code', 'fields' => ['integrations' => ['gsc_verification']], 'text' => '<p>After saving, go back to Search Console and press <b>Verify</b>.</p>'],
                ['title' => 'Submit your sitemap', 'copy' => ['Sitemap' => site_url('sitemap.xml')],
                    'text' => '<p>In Search Console open <b>Sitemaps</b>, paste the sitemap address and press <b>Submit</b>. Google then finds every product and category on its own.</p>'],
            ],
        ],
        'ga4' => [
            'title' => 'Google Analytics 4', 'icon' => 'chart', 'time' => '6 min', 'area' => 'Google',
            'intro' => 'Visitors, traffic sources and sales — including add-to-cart, checkout and purchase with revenue.',
            'done' => fn() => (bool) setting('ga4_id'),
            'steps' => [
                ['title' => 'Create a property', 'link' => ['Open Google Analytics', 'https://analytics.google.com/'],
                    'text' => '<ol><li>Open <b>Admin</b> (gear icon, bottom left) → <b>Create → Property</b>.</li><li>Name: The Gift Boxx, time zone India, currency Indian Rupee.</li><li>Business type: Shopping / retail.</li></ol><p>Already had Analytics on the old site? Just open that property instead — history is kept.</p>'],
                ['title' => 'Add a web stream', 'text' => '<ol><li><b>Admin → Data streams → Add stream → Web</b>.</li><li>Website: <b>' . e($live) . '</b>, name: Website. Keep Enhanced measurement on.</li><li>Copy the <b>Measurement ID</b> (starts with <b>G-</b>).</li></ol>'],
                ['title' => 'Paste the Measurement ID', 'fields' => ['integrations' => ['ga4_id']]],
                ['title' => 'Check it', 'test' => '/channels/test/ga4', 'text' => '<p>We look for the tag on your homepage. In Analytics, <b>Reports → Realtime</b> shows you within a minute once you open the site. Purchases are sent automatically with their value.</p>'],
            ],
        ],
        'gads' => [
            'title' => 'Google Ads conversions', 'icon' => 'tag', 'time' => '8 min', 'area' => 'Google',
            'intro' => 'Tell Google Ads which clicks turned into orders, so campaigns optimise for sales.',
            'done' => fn() => (bool) setting('gads_id'),
            'steps' => [
                ['title' => 'Create a Purchase conversion', 'link' => ['Open Google Ads', 'https://ads.google.com/aw/conversions'],
                    'text' => '<ol><li>Open <b>Goals → Conversions → Summary</b> and click <b>+ New conversion action</b>.</li><li>Choose <b>Website</b>, enter <b>' . e($live) . '</b> and scan.</li><li>Pick <b>Add a conversion action manually</b>: category <b>Purchase</b>, value <b>Use different values for each conversion</b>, count <b>Every</b>.</li></ol>'],
                ['title' => 'Copy the ID and label', 'text' => '<ol><li>Under <b>Tag setup</b> choose <b>Install the tag yourself</b>.</li><li>In the event snippet find <code>send_to: \'AW-123456789/AbCdEfGh\'</code>.</li><li>The part before the slash is the <b>Conversion ID</b> (AW-123456789), after it is the <b>label</b>.</li></ol><p>You don’t need to paste any code — the store adds it.</p>'],
                ['title' => 'Paste them here', 'fields' => ['integrations' => ['gads_id', 'gads_label']]],
                ['title' => 'Link your accounts (recommended)', 'text' => '<p>In Google Ads open <b>Tools → Data manager</b> and link <b>Google Analytics</b> and <b>Merchant Center</b>. That lets you run Shopping and Performance Max campaigns with your products.</p>'],
            ],
        ],
        'gmc' => [
            'title' => 'Google Merchant Center', 'icon' => 'bag', 'time' => '15 min', 'area' => 'Google',
            'intro' => 'Show your boxes with photo and price in Google Shopping and the free product listings.',
            'done' => fn() => (bool) setting('gmc_id'),
            'steps' => [
                ['title' => 'Open Merchant Center', 'link' => ['Open Merchant Center', 'https://merchants.google.com/'],
                    'text' => '<p>Sign in with the same Google account you use for Search Console. Fill in your business name, country (India) and website. If the site is verified in Search Console, Merchant Center verifies and claims it automatically.</p>'],
                ['title' => 'Add your product feed', 'copy' => ['Feed URL' => site_url('feeds/google.xml')],
                    'text' => '<ol><li>Open <b>Products → Add products</b> (or <b>Data sources → Add product source</b>).</li><li>Choose <b>Add products from a file</b> → <b>Enter a link to your file</b>.</li><li>Paste the feed URL and set it to fetch <b>daily</b>.</li></ol><p>New products and price changes then reach Google every day.</p>'],
                ['title' => 'Shipping and returns', 'text' => '<ol><li><b>Settings → Shipping and returns → Add shipping service</b>: India, delivery in 5–7 days, flat rate of ' . e(money((float) setting('shipping_flat'))) . (((float) setting('shipping_free_above')) > 0 ? ', free above ' . e(money((float) setting('shipping_free_above'))) : '') . '.</li><li>Add a return policy that matches your refund page.</li></ol>'],
                ['title' => 'Save your Merchant Center ID', 'fields' => ['integrations' => ['gmc_id']], 'text' => '<p>The number at the top right of Merchant Center, next to your business name.</p>'],
                ['title' => 'Check your products', 'link' => ['Open Sales channels', '/channels'], 'text' => '<p>The Sales channels page shows each product’s readiness for Google (photo, price, description) so you can fix issues before Google flags them.</p>'],
            ],
        ],
        'meta' => [
            'title' => 'Meta (Facebook & Instagram)', 'icon' => 'facebook', 'time' => '15 min', 'area' => 'Social',
            'intro' => 'Pixel + Conversions API for accurate ad tracking, domain verification and an Instagram shop catalog.',
            'done' => fn() => (bool) setting('meta_pixel_id'),
            'steps' => [
                ['title' => 'Create or open your dataset (Pixel)', 'link' => ['Open Events Manager', 'https://business.facebook.com/events_manager2'],
                    'text' => '<ol><li>In Events Manager click <b>Connect data sources → Web</b> (skip if you already have a Pixel from the old site — just select it).</li><li>Copy the <b>Dataset / Pixel ID</b> (a long number).</li></ol>'],
                ['title' => 'Generate a Conversions API token', 'text' => '<ol><li>Select your Pixel → <b>Settings</b>.</li><li>Under <b>Conversions API</b> click <b>Generate access token</b> and copy it.</li></ol><p>This sends purchases from the server too, so ad blockers and iPhones don’t hide your sales from Meta.</p>'],
                ['title' => 'Paste the ID and token', 'fields' => ['integrations' => ['meta_pixel_id', 'meta_capi_token']]],
                ['title' => 'Verify your domain', 'link' => ['Open Business settings', 'https://business.facebook.com/settings/owned-domains'],
                    'text' => '<ol><li><b>Business settings → Brand safety → Domains → Add</b> your domain <b>' . e($live) . '</b>.</li><li>Choose <b>Meta-tag</b> and copy only the <code>content</code> value.</li></ol>', 'fields' => ['integrations' => ['meta_domain_verification']]],
                ['title' => 'Connect your catalog', 'copy' => ['Catalog feed URL' => site_url('feeds/meta.xml')], 'link' => ['Open Commerce Manager', 'https://business.facebook.com/commerce'],
                    'text' => '<ol><li><b>Commerce Manager → Add catalog → E-commerce</b>.</li><li>In the catalog open <b>Data sources → Add items → Data feed</b>.</li><li>Choose <b>Scheduled feed</b>, paste the URL and pick <b>daily</b>, currency INR.</li></ol>'],
                ['title' => 'Check it', 'test' => '/channels/test/meta', 'text' => '<p>In Events Manager use <b>Test events</b> to watch page views and add-to-carts arrive as you browse the site.</p>'],
            ],
        ],
        'pinterest' => [
            'title' => 'Pinterest', 'icon' => 'pin', 'time' => '10 min', 'area' => 'Social',
            'intro' => 'Claim your website, track sales from Pins and turn your products into shoppable Pins.',
            'done' => fn() => (bool) (setting('pinterest_tag_id') || setting('pinterest_verification')),
            'steps' => [
                ['title' => 'Claim your website', 'link' => ['Open Pinterest settings', 'https://www.pinterest.com/settings/claim'],
                    'text' => '<ol><li>Use a Pinterest <b>business</b> account.</li><li><b>Settings → Claimed accounts → Websites → Claim</b>.</li><li>Choose <b>Add HTML tag</b> and copy only the <code>content</code> value.</li></ol>', 'fields' => ['integrations' => ['pinterest_verification']]],
                ['title' => 'Add the Pinterest tag', 'link' => ['Open Conversions', 'https://ads.pinterest.com/'],
                    'text' => '<p>In <b>Ads → Conversions</b> create a Pinterest tag and copy its <b>Tag ID</b> (a long number).</p>', 'fields' => ['integrations' => ['pinterest_tag_id']]],
                ['title' => 'Connect your catalog', 'copy' => ['Catalog feed URL' => site_url('feeds/pinterest.xml')],
                    'text' => '<p><b>Ads → Catalogs → Create catalog → Retail → Data source</b>: paste the feed URL, format XML, currency INR. Pinterest checks it daily.</p>'],
            ],
        ],
        'google_login' => [
            'title' => 'Google sign-in', 'icon' => 'user', 'time' => '10 min', 'area' => 'Google',
            'intro' => 'Adds “Continue with Google” so customers can create an account in one tap (still optional — guests can check out as before). Your team can use it for the admin too.',
            'done' => fn() => google_login_ready(),
            'steps' => [
                ['title' => 'Create a Google Cloud project', 'link' => ['Open Google Cloud', 'https://console.cloud.google.com/projectcreate'],
                    'text' => '<p>It’s free — no card needed for sign-in. Sign in with your business Google account, name the project <b>The Gift Boxx</b> and press <b>Create</b>. Make sure the new project is selected at the top of the page.</p>'],
                ['title' => 'Set up the consent screen', 'link' => ['Open Google Auth Platform', 'https://console.cloud.google.com/auth/overview'],
                    'text' => '<ol><li>Click <b>Get started</b>.</li><li><b>App name:</b> ' . e(setting('store_name')) . '. <b>Support email:</b> your email.</li><li><b>Audience:</b> choose <b>External</b>.</li><li>Add your contact email, agree and press <b>Create</b>.</li><li>Open <b>Audience</b> and press <b>Publish app</b> so every customer can sign in (not just test users). For basic sign-in (name + email) Google doesn’t need to review the app.</li></ol><p>Optional: under <b>Branding</b> add your logo and your website, privacy and terms links.</p>'],
                ['title' => 'Create the sign-in client', 'link' => ['Open Clients', 'https://console.cloud.google.com/auth/clients/create'],
                    'copy' => ['Authorised JavaScript origin 1' => rtrim(site_url(), '/'), 'Authorised JavaScript origin 2' => rtrim(admin_url(), '/'),
                        'Authorised redirect URI 1 (shop)' => google_redirect_uri('store'), 'Authorised redirect URI 2 (admin)' => google_redirect_uri('admin')],
                    'text' => '<ol><li><b>Application type:</b> Web application. <b>Name:</b> Website.</li><li>Under <b>Authorised JavaScript origins</b> press <b>Add URI</b> and paste both origins below.</li><li>Under <b>Authorised redirect URIs</b> add both redirect URIs below — exactly, including the ending.</li><li>Press <b>Create</b>. A box shows your <b>Client ID</b> and <b>Client secret</b> — copy both (you can also download them).</li></ol><p>When you move from the test address to thegiftboxx.com, add the new addresses here too.</p>'],
                ['title' => 'Paste the keys and switch it on', 'fields' => ['integrations' => ['google_client_id', 'google_client_secret', 'google_login_enabled', 'google_admin_login']],
                    'text' => '<p>The secret is stored encrypted. Admin sign-in only works for emails already added under <b>Team</b>.</p>'],
                ['title' => 'Try it', 'link' => ['Open your account page', site_url('my-account/')],
                    'text' => '<p>Open your shop’s account page in a private window and press <b>Continue with Google</b>. You should come back signed in.</p><p>If Google shows <b>redirect_uri_mismatch</b>, the redirect URI in step 3 doesn’t match exactly — copy it again.</p>'],
            ],
        ],
        'code' => [
            'title' => 'Header code & verification tags', 'icon' => 'page', 'time' => '3 min', 'area' => 'Other',
            'intro' => 'Paste verification tags and tracking codes (Google, Meta Pixel and any other script) that belong in the <head> of your site.',
            'done' => fn() => (bool) (setting('gsc_verification') || setting('meta_pixel_id') || trim((string) setting('header_code'))),
            'steps' => [
                ['title' => 'Google site verification', 'fields' => ['integrations' => ['gsc_verification']],
                    'text' => '<p>Google asks you to “add this meta tag to the &lt;head&gt; of your site”. Paste the whole tag here exactly as Google shows it, for example <code>&lt;meta name="google-site-verification" content="…" /&gt;</code>. It goes into every page automatically.</p><p>Then press <b>Verify</b> in Google.</p>'],
                ['title' => 'Meta Pixel', 'fields' => ['integrations' => ['meta_pixel_id', 'meta_domain_verification']],
                    'text' => '<p>Paste your Pixel ID <b>or the whole Pixel code</b> from Meta Events Manager — the store reads the ID from it and adds the Pixel with page-view, add-to-cart, checkout and purchase tracking.</p><p>Meta’s domain-verification tag can be pasted in full too.</p>'],
                ['title' => 'Any other code', 'fields' => ['integrations' => ['header_code', 'footer_code']],
                    'text' => '<p>For anything else a service asks you to put in the header — Microsoft Clarity, Hotjar, another verification tag — paste it into <b>Header code</b>. Chat widgets usually go into <b>Footer code</b>.</p><p>Don’t paste Google Analytics, Google Ads or the Meta Pixel here as well, or they’ll count every visit twice.</p>'],
                ['title' => 'Check your site', 'link' => ['Open your site', $site . '/'],
                    'text' => '<p>Open your site, right-click and choose <b>View page source</b>. Press Ctrl+F (⌘F on Mac) and search for the code — you’ll see it near the top.</p><p>Changes appear straight away. If you use a cache (Hostinger CDN), it may take a few minutes.</p>'],
            ],
        ],
        'whatsapp' => [
            'title' => 'WhatsApp', 'icon' => 'whatsapp', 'time' => '1 min', 'area' => 'Social',
            'intro' => 'The chat button on your site and on product pages opens WhatsApp with a ready message.',
            'done' => fn() => (bool) setting('whatsapp_number'),
            'steps' => [
                ['title' => 'Your WhatsApp number', 'fields' => ['integrations' => ['whatsapp_number', 'whatsapp_message'], 'website' => ['whatsapp_float', 'product_whatsapp']],
                    'text' => '<p>Country code and number, digits only — for India that’s 91 followed by the 10-digit number.</p>'],
            ],
        ],
    ];
}

function admin_setup(): void
{
    require_admin(true);
    admin_view('setup', ['wizards' => setup_wizards()], 'Guided setup', 'setup');
}

/** One settings field, rendered the same way as on the settings pages. */
function setting_input(string $key, array $f): string
{
    $val = setting($key);
    $label = '<span class="row-label">' . e($f['label']) . (!empty($f['help']) ? '<small>' . e($f['help']) . '</small>' : '') . '</span>';
    switch ($f['type']) {
        case 'bool':
            return '<div class="row">' . $label . '<span class="switch"><input type="checkbox" name="' . e($key) . '" value="1"' . ((string) $val === '1' ? ' checked' : '') . '><span></span></span></div>';
        case 'secret':
            $hint = secret_hint($key);
            return '<div class="row col">' . $label . '<input type="password" name="' . e($key) . '" autocomplete="new-password" placeholder="' . ($hint ? e($hint) . ' — saved. Type to replace' : 'Paste here') . '">'
                . ($hint ? '<span class="badge b-ok">' . icon('check') . ' Saved securely</span>' : '') . '</div>';
        case 'textarea':
        case 'code':
            return '<div class="row col">' . $label . '<textarea name="' . e($key) . '" rows="3">' . e($val) . '</textarea></div>';
        case 'select':
            $o = '';
            foreach (field_options($f) as $k => $l) {
                $o .= '<option value="' . e($k) . '"' . ((string) $val === (string) $k ? ' selected' : '') . '>' . e($l) . '</option>';
            }
            return '<div class="row">' . $label . '<select name="' . e($key) . '">' . $o . '</select></div>';
        case 'number':
            return '<div class="row">' . $label . '<input type="number" step="any" min="0" name="' . e($key) . '" value="' . e($val) . '" style="max-width:140px"></div>';
        case 'image':
            return '<div class="row col">' . $label . image_field($key, $val ?: null, 'Upload') . '</div>';
        case 'color':
            return '<div class="row">' . $label . '<span class="color-field"><input type="color" name="' . e($key) . '" value="' . e($val) . '"><code>' . e(strtoupper((string) $val)) . '</code></span></div>';
        default:
            return '<div class="row col">' . $label . '<input name="' . e($key) . '" value="' . e($val) . '" placeholder="' . e($f['placeholder'] ?? '') . '"></div>';
    }
}
