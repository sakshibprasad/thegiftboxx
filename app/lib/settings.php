<?php
declare(strict_types=1);

/**
 * Every setting the admin "Settings" screens can edit.
 * type: text | textarea | secret | bool | select | number | code
 * Secrets are encrypted in the database and never shown back in full.
 */
function settings_schema(): array
{
    return [
        'store' => [
            'label' => 'Store',
            'icon' => 'store',
            'fields' => [
                'store_name' => ['label' => 'Store name', 'type' => 'text', 'default' => 'The Gift Boxx'],
                'store_tagline' => ['label' => 'Tagline', 'type' => 'text', 'default' => 'Premium Gift Hampers for Every Occasion'],
                'store_email' => ['label' => 'Store email (receives order alerts)', 'type' => 'text', 'default' => 'support@thegiftboxx.com'],
                'store_phone' => ['label' => 'Phone', 'type' => 'text', 'default' => '+91 77109 70512'],
                'store_address' => ['label' => 'Address', 'type' => 'textarea', 'default' => "Sector-1, Panvel\nNavi Mumbai, Maharashtra - 410206"],
                'store_hours' => ['label' => 'Work hours', 'type' => 'textarea', 'default' => "Monday to Friday: 10am - 10pm\nWeekends: 10am - 9pm"],
                'store_gstin' => ['label' => 'GSTIN (shown on invoices)', 'type' => 'text', 'default' => ''],
                'order_prefix' => ['label' => 'Order number prefix', 'type' => 'text', 'default' => 'TGB'],
                'instagram_url' => ['label' => 'Instagram URL', 'type' => 'text', 'default' => ''],
                'facebook_url' => ['label' => 'Facebook URL', 'type' => 'text', 'default' => ''],
                'announcement' => ['label' => 'Announcement bar text (leave empty to hide)', 'type' => 'text', 'default' => 'Premium gift boxes, curated with love and delivered across India'],
                'youtube_url' => ['label' => 'YouTube URL', 'type' => 'text', 'default' => ''],
                'pinterest_url' => ['label' => 'Pinterest URL', 'type' => 'text', 'default' => ''],
                'linkedin_url' => ['label' => 'LinkedIn URL', 'type' => 'text', 'default' => ''],
            ],
        ],
        'website' => [
            'label' => 'Website',
            'icon' => 'external',
            'fields' => [
                'maintenance_mode' => ['label' => 'Maintenance mode (visitors see a “back soon” page)', 'type' => 'bool', 'default' => '0'],
                'maintenance_title' => ['label' => 'Maintenance heading', 'type' => 'text', 'default' => 'We’re wrapping something special'],
                'maintenance_text' => ['label' => 'Maintenance message', 'type' => 'textarea', 'default' => 'Our store is getting a little refresh and will be back very soon. For urgent orders, WhatsApp or call us.'],
                'checkout_disabled' => ['label' => 'Pause new orders (holiday mode) – site stays visible', 'type' => 'bool', 'default' => '0'],
                'checkout_disabled_text' => ['label' => 'Holiday message shown in cart & checkout', 'type' => 'text', 'default' => 'We’re taking a short break and will start accepting orders again soon.'],
                'logo_image' => ['label' => 'Logo for light backgrounds (SVG or PNG, optional)', 'type' => 'image'],
                'logo_image_light' => ['label' => 'Logo for dark backgrounds / footer (optional)', 'type' => 'image'],
                'og_image' => ['label' => 'Default image when your site is shared (1200×630)', 'type' => 'image'],
                'seo_home_title' => ['label' => 'Homepage title for Google', 'type' => 'text', 'default' => 'Premium Gift Hampers & Wooden Gift Boxes in India | The Gift Boxx'],
                'seo_home_description' => ['label' => 'Homepage description for Google', 'type' => 'textarea', 'default' => 'Curated premium gift hampers in reusable wooden boxes. Birthday, Valentine’s, wedding, wellness and corporate gift boxes, delivered ready to gift across India.'],
                'noindex_site' => ['label' => 'Hide the whole site from Google (only for testing!)', 'type' => 'bool', 'default' => '0'],
                'favicon' => ['label' => 'Browser tab icon (square PNG, 512×512)', 'type' => 'image'],
                'footer_statement' => ['label' => 'Footer headline', 'type' => 'text', 'default' => 'Gifts they’ll keep.'],
                'footer_text' => ['label' => 'Footer line under the headline', 'type' => 'text', 'default' => 'Tell us who it’s for. We’ll take care of everything else.'],
                'footer_image' => ['label' => 'Footer photo', 'type' => 'image', 'help' => 'Shown beside the footer headline, softly fading into it. Empty = your homepage photo.'],
                'footer_tagline' => ['label' => 'Footer text under the logo', 'type' => 'textarea', 'default' => 'Premium gift hampers in wooden boxes. Curated with love, delivered across India.'],
                'seo_separator' => ['label' => 'Separator in page titles', 'type' => 'select', 'options' => ['|' => 'Page | The Gift Boxx', '–' => 'Page – The Gift Boxx', '·' => 'Page · The Gift Boxx'], 'default' => '|'],
                'min_order_amount' => ['label' => 'Minimum order value (₹, 0 = none)', 'type' => 'number', 'default' => '0'],
                'guest_checkout' => ['label' => 'Allow checkout without an account', 'type' => 'bool', 'default' => '1'],
                'search_enabled' => ['label' => 'Show search', 'type' => 'bool', 'default' => '1'],
                'product_whatsapp' => ['label' => 'Show “Chat on WhatsApp” on product pages', 'type' => 'bool', 'default' => '1'],
                'related_enabled' => ['label' => 'Show “You may also like” on product pages', 'type' => 'bool', 'default' => '1'],
                'reviews_auto_approve' => ['label' => 'Publish new reviews without approval', 'type' => 'bool', 'default' => '0'],
                'whatsapp_float' => ['label' => 'Show floating WhatsApp button', 'type' => 'bool', 'default' => '1'],
                'blog_enabled' => ['label' => 'Show the Blog on the website (menu, /blog/ and sitemap)', 'type' => 'bool', 'default' => '0'],
                'reviews_enabled' => ['label' => 'Allow customers to write reviews', 'type' => 'bool', 'default' => '1'],
                'wishlist_enabled' => ['label' => 'Show wishlist hearts', 'type' => 'bool', 'default' => '1'],
                'cookie_notice' => ['label' => 'Show cookie notice', 'type' => 'bool', 'default' => '1'],
            ],
        ],
        'appearance' => [
            'label' => 'Appearance',
            'icon' => 'palette',
            'fields' => [
                'theme_bg' => ['label' => 'Page background', 'type' => 'color', 'default' => '#FBF8F4'],
                'theme_surface' => ['label' => 'Cards & panels', 'type' => 'color', 'default' => '#FFFFFF'],
                'theme_text' => ['label' => 'Text colour', 'type' => 'color', 'default' => '#1D1714'],
                'theme_accent' => ['label' => 'Accent (buttons, highlights)', 'type' => 'color', 'default' => '#BAA183'],
                'theme_dark' => ['label' => 'Dark sections (header, footer)', 'type' => 'color', 'default' => '#241913'],
                'theme_heading_font' => ['label' => 'Heading font', 'type' => 'select', 'options' => 'heading_fonts', 'default' => 'Cormorant Garamond'],
                'theme_body_font' => ['label' => 'Body font', 'type' => 'select', 'options' => 'body_fonts', 'default' => 'Inter'],
                'theme_radius' => ['label' => 'Corner rounding', 'type' => 'select', 'options' => ['0' => 'Sharp', '8' => 'Subtle', '16' => 'Soft (Apple-like)', '28' => 'Extra round'], 'default' => '16'],
                'admin_accent' => ['label' => 'Admin accent colour', 'type' => 'color', 'default' => '#0A84FF'],
                'admin_toggle' => ['label' => 'Admin switch colour (when on)', 'type' => 'color', 'default' => '#BAA183'],
                'admin_theme' => ['label' => 'Admin appearance', 'type' => 'select', 'options' => ['auto' => 'Match device (light/dark)', 'light' => 'Light', 'dark' => 'Dark'], 'default' => 'auto'],
            ],
        ],
        'payments' => [
            'label' => 'Payments',
            'icon' => 'card',
            'fields' => [
                'payu_enabled' => ['label' => 'Enable PayU', 'type' => 'bool', 'default' => '0'],
                'payu_mode' => ['label' => 'PayU mode', 'type' => 'select', 'options' => ['test' => 'Test', 'live' => 'Live'], 'default' => 'test'],
                'payu_key' => ['label' => 'PayU Merchant Key', 'type' => 'secret', 'help' => 'PayU Dashboard → Developers → API Keys'],
                'payu_salt' => ['label' => 'PayU Salt (v1)', 'type' => 'secret'],
                'payu_title' => ['label' => 'Title shown at checkout', 'type' => 'text', 'default' => 'UPI, Cards, Netbanking (PayU)'],
                'cashfree_enabled' => ['label' => 'Enable Cashfree', 'type' => 'bool', 'default' => '0'],
                'cashfree_mode' => ['label' => 'Cashfree mode', 'type' => 'select', 'options' => ['sandbox' => 'Sandbox (test)', 'production' => 'Production (live)'], 'default' => 'sandbox'],
                'cashfree_app_id' => ['label' => 'Cashfree App ID (Client ID)', 'type' => 'secret', 'help' => 'Cashfree Dashboard → Developers → API Keys'],
                'cashfree_secret' => ['label' => 'Cashfree Secret Key', 'type' => 'secret'],
                'cashfree_title' => ['label' => 'Title shown at checkout', 'type' => 'text', 'default' => 'UPI, Cards, Wallets (Cashfree)'],
                'cod_enabled' => ['label' => 'Enable Cash on Delivery', 'type' => 'bool', 'default' => '0'],
                'cod_fee' => ['label' => 'COD extra fee (₹)', 'type' => 'number', 'default' => '0'],
                'cod_max' => ['label' => 'Hide COD above order total (₹, 0 = no limit)', 'type' => 'number', 'default' => '0'],
            ],
        ],
        'shipping' => [
            'label' => 'Shipping',
            'icon' => 'truck',
            'fields' => [
                'shipping_flat' => ['label' => 'Flat shipping charge (₹)', 'type' => 'number', 'default' => '99'],
                'shipping_free_above' => ['label' => 'Free shipping above (₹, 0 = always charge)', 'type' => 'number', 'default' => '10000'],
                'shipping_free_highlight' => ['label' => 'Mention free delivery on the website (cart progress bar, product page)', 'type' => 'bool', 'default' => '0'],
                'shipping_eta' => ['label' => 'Delivery time text', 'type' => 'text', 'default' => 'Packed to order · Delivered across India in 5–7 days'],
                'shiprocket_enabled' => ['label' => 'Enable Shiprocket', 'type' => 'bool', 'default' => '0'],
                'shiprocket_email' => ['label' => 'Shiprocket API user email', 'type' => 'text', 'help' => 'Shiprocket → Settings → API → Configure → Create API User'],
                'shiprocket_password' => ['label' => 'Shiprocket API user password', 'type' => 'secret'],
                'shiprocket_pickup' => ['label' => 'Pickup location name (exactly as in Shiprocket)', 'type' => 'text', 'default' => 'Primary'],
                'shiprocket_pickup_pincode' => ['label' => 'Pickup pincode', 'type' => 'text', 'default' => '410206'],
                'shiprocket_auto' => ['label' => 'Send paid orders to Shiprocket automatically', 'type' => 'bool', 'default' => '0'],
                'delivery_date_enabled' => ['label' => 'Ask for preferred delivery date at checkout', 'type' => 'bool', 'default' => '1'],
                'delivery_min_days' => ['label' => 'First delivery date customers can pick = today + this many days (7 = the next 6 days are blocked)', 'type' => 'number', 'default' => '7'],
                'delivery_max_days' => ['label' => 'Latest delivery date customers can pick = today + (days)', 'type' => 'number', 'default' => '60'],
                'gift_message_enabled' => ['label' => 'Show gift message card field at checkout', 'type' => 'bool', 'default' => '1'],
                'pincode_check' => ['label' => 'Show pincode delivery check on product pages', 'type' => 'bool', 'default' => '1'],
            ],
        ],
        'integrations' => [
            'label' => 'Google, Meta & more',
            'icon' => 'plug',
            'fields' => [
                'ga4_id' => ['label' => 'Google Analytics 4 Measurement ID', 'type' => 'text', 'placeholder' => 'G-XXXXXXXXXX', 'help' => 'Analytics → Admin → Data streams → your site. You can also paste the whole Google tag code.'],
                'gsc_verification' => ['label' => 'Google site verification (Search Console)', 'type' => 'text', 'placeholder' => '<meta name="google-site-verification" content="…">', 'help' => 'Paste the whole meta tag Google gives you, or just the code inside content="…". It’s added to the <head> of every page.'],
                'gtm_id' => ['label' => 'Google Tag Manager ID (optional)', 'type' => 'text', 'placeholder' => 'GTM-XXXXXXX'],
                'gads_id' => ['label' => 'Google Ads Conversion ID', 'type' => 'text', 'placeholder' => 'AW-123456789'],
                'gads_label' => ['label' => 'Google Ads Purchase conversion label', 'type' => 'text', 'placeholder' => 'AbCdEfGhIjk'],
                'gmc_id' => ['label' => 'Google Merchant Center ID', 'type' => 'text', 'help' => 'Add the product feed URL shown below in Merchant Center → Products → Feeds'],
                'meta_pixel_id' => ['label' => 'Meta (Facebook) Pixel', 'type' => 'text', 'placeholder' => '123456789012345', 'help' => 'Paste the Pixel ID or the whole Pixel code from Events Manager — the ID is picked out and the Pixel is added to the <head> with purchase tracking. Don’t also paste it under Custom code.'],
                'meta_capi_token' => ['label' => 'Meta Conversions API access token', 'type' => 'secret', 'help' => 'Events Manager → your pixel → Settings → Conversions API → Generate access token'],
                'meta_domain_verification' => ['label' => 'Meta domain verification', 'type' => 'text', 'placeholder' => '<meta name="facebook-domain-verification" content="…">', 'help' => 'Paste the whole meta tag or just the code.'],
                'pinterest_tag_id' => ['label' => 'Pinterest Tag ID', 'type' => 'text', 'help' => 'Pinterest Business → Ads → Conversions → Pinterest Tag'],
                'pinterest_verification' => ['label' => 'Pinterest site verification', 'type' => 'text', 'placeholder' => '<meta name="p:domain_verify" content="…">', 'help' => 'Paste the whole meta tag or just the code.'],
                'bing_verification' => ['label' => 'Bing site verification', 'type' => 'text', 'placeholder' => '<meta name="msvalidate.01" content="…">', 'help' => 'Paste the whole meta tag or just the code.'],
                'whatsapp_number' => ['label' => 'WhatsApp number (with country code, digits only)', 'type' => 'text', 'default' => '917710970512'],
                'whatsapp_message' => ['label' => 'WhatsApp default message', 'type' => 'text', 'default' => 'Hi! I would like to know more about your gift boxes.'],
                'header_code' => ['label' => 'Header code — added inside <head> on every page', 'type' => 'code', 'help' => 'For any other verification tag or tracking script (Microsoft Clarity, Hotjar, a second pixel…). Google, Meta, Pinterest and Bing have their own boxes above — use those instead so nothing loads twice.'],
                'footer_code' => ['label' => 'Footer code — added just before </body>', 'type' => 'code', 'help' => 'For chat widgets and scripts that ask to go at the end of the page.'],
            ],
        ],
        'email' => [
            'label' => 'Email',
            'icon' => 'mail',
            'fields' => [
                'smtp_host' => ['label' => 'SMTP host', 'type' => 'text', 'default' => 'smtp.hostinger.com'],
                'smtp_port' => ['label' => 'SMTP port', 'type' => 'number', 'default' => '465'],
                'smtp_secure' => ['label' => 'Encryption', 'type' => 'select', 'options' => ['ssl' => 'SSL (465)', 'tls' => 'STARTTLS (587)', 'none' => 'None'], 'default' => 'ssl'],
                'smtp_user' => ['label' => 'SMTP username (full email address)', 'type' => 'text', 'default' => ''],
                'smtp_pass' => ['label' => 'SMTP password', 'type' => 'secret'],
                'mail_from_name' => ['label' => 'From name', 'type' => 'text', 'default' => 'The Gift Boxx'],
                'abandoned_enabled' => ['label' => 'Send abandoned cart reminders', 'type' => 'bool', 'default' => '1'],
                'abandoned_delay_hours' => ['label' => 'First reminder after (hours)', 'type' => 'number', 'default' => '1'],
                'abandoned_coupon' => ['label' => 'Coupon code to include in reminder (optional)', 'type' => 'text', 'default' => ''],
            ],
        ],
    ];
}

function settings_all(): array
{
    if (!isset($GLOBALS['__settings'])) {
        $GLOBALS['__settings'] = [];
        try {
            foreach (all('SELECT skey, svalue, encrypted FROM settings') as $row) {
                $GLOBALS['__settings'][$row['skey']] = $row;
            }
        } catch (Throwable $e) {
            // Database not ready yet (installer).
        }
    }
    return $GLOBALS['__settings'];
}

function setting_default(string $key)
{
    foreach (settings_schema() as $group) {
        if (isset($group['fields'][$key])) {
            return $group['fields'][$key]['default'] ?? '';
        }
    }
    return '';
}

function setting(string $key, $default = null)
{
    $all = settings_all();
    if (!isset($all[$key])) {
        return $default ?? setting_default($key);
    }
    $row = $all[$key];
    return $row['encrypted'] ? decrypt_value($row['svalue']) : $row['svalue'];
}

function setting_on(string $key): bool
{
    return (string) setting($key) === '1';
}

function setting_json(string $key, array $default = []): array
{
    $v = setting($key, '');
    $d = $v ? json_decode((string) $v, true) : null;
    return is_array($d) ? $d : $default;
}

function setting_set(string $key, $value, bool $encrypt = false): void
{
    $value = (string) $value;
    $stored = $encrypt && $value !== '' ? encrypt_value($value) : $value;
    if (one('SELECT skey FROM settings WHERE skey = ?', [$key])) {
        update('settings', ['svalue' => $stored, 'encrypted' => $encrypt ? 1 : 0], 'skey = ?', [$key]);
    } else {
        insert('settings', ['skey' => $key, 'svalue' => $stored, 'encrypted' => $encrypt ? 1 : 0]);
    }
    unset($GLOBALS['__settings']);
}

function secret_hint(string $key): string
{
    $v = (string) setting($key, '');
    if ($v === '') {
        return '';
    }
    return str_repeat('•', 8) . substr($v, -4);
}

function font_options(string $set): array
{
    $heading = ['Cormorant Garamond', 'Playfair Display', 'Fraunces', 'DM Serif Display', 'Libre Baskerville', 'Italiana', 'Marcellus', 'Inter', 'Manrope'];
    $body = ['Inter', 'Manrope', 'DM Sans', 'Jost', 'Outfit', 'Lato', 'Nunito Sans', 'Work Sans', 'Source Sans 3'];
    $list = $set === 'heading_fonts' ? $heading : $body;
    return array_combine($list, $list);
}

function field_options(array $field): array
{
    $o = $field['options'] ?? [];
    return is_string($o) ? font_options($o) : $o;
}

/**
 * People often paste the whole snippet Google, Meta or Pinterest gives them
 * (a <meta> tag or a <script>). Keep only the ID or code the store needs.
 */
function setting_extract(string $key, string $val): string
{
    $val = trim($val);
    if ($val === '') {
        return '';
    }
    $grab = fn(string $re) => preg_match($re, $val, $m) ? $m[1] : null;
    $found = match ($key) {
        'gsc_verification', 'bing_verification', 'meta_domain_verification', 'pinterest_verification'
            => $grab('/content\s*=\s*["\']([^"\']+)["\']/i'),
        'ga4_id' => $grab('/\b(G-[A-Z0-9]{4,})\b/i'),
        'gtm_id' => $grab('/\b(GTM-[A-Z0-9]{4,})\b/i'),
        'gads_id' => $grab('/\b(AW-\d{6,})\b/i'),
        'gads_label' => $grab('/AW-\d+\/([\w-]+)/i'),
        'meta_pixel_id' => $grab('/fbq\(\s*["\']init["\']\s*,\s*["\'](\d+)["\']/') ?? $grab('/facebook\.com\/tr\?id=(\d+)/') ?? $grab('/^\s*(\d{8,20})\s*$/'),
        'pinterest_tag_id' => $grab('/pintrk\(\s*["\']load["\']\s*,\s*["\'](\d+)["\']/') ?? $grab('/^\s*(\d{8,20})\s*$/'),
        default => null,
    };
    return $found !== null ? (in_array($key, ['ga4_id', 'gtm_id', 'gads_id'], true) ? strtoupper($found) : $found) : $val;
}
