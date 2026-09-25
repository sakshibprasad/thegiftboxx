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
                'announcement' => ['label' => 'Announcement bar text (leave empty to hide)', 'type' => 'text', 'default' => 'Free delivery across India on orders above ₹2,999'],
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
                'shipping_free_above' => ['label' => 'Free shipping above (₹, 0 = always charge)', 'type' => 'number', 'default' => '2999'],
                'shipping_eta' => ['label' => 'Delivery time text', 'type' => 'text', 'default' => 'Ships in 2–3 days · Delivered in 4–7 days'],
                'shiprocket_enabled' => ['label' => 'Enable Shiprocket', 'type' => 'bool', 'default' => '0'],
                'shiprocket_email' => ['label' => 'Shiprocket API user email', 'type' => 'text', 'help' => 'Shiprocket → Settings → API → Configure → Create API User'],
                'shiprocket_password' => ['label' => 'Shiprocket API user password', 'type' => 'secret'],
                'shiprocket_pickup' => ['label' => 'Pickup location name (exactly as in Shiprocket)', 'type' => 'text', 'default' => 'Primary'],
                'shiprocket_pickup_pincode' => ['label' => 'Pickup pincode', 'type' => 'text', 'default' => '410206'],
                'shiprocket_auto' => ['label' => 'Send paid orders to Shiprocket automatically', 'type' => 'bool', 'default' => '0'],
                'delivery_date_enabled' => ['label' => 'Ask for preferred delivery date at checkout', 'type' => 'bool', 'default' => '1'],
                'delivery_min_days' => ['label' => 'Earliest delivery date = today + (days)', 'type' => 'number', 'default' => '5'],
                'gift_message_enabled' => ['label' => 'Show gift message card field at checkout', 'type' => 'bool', 'default' => '1'],
                'pincode_check' => ['label' => 'Show pincode delivery check on product pages', 'type' => 'bool', 'default' => '1'],
            ],
        ],
        'integrations' => [
            'label' => 'Google, Meta & more',
            'icon' => 'plug',
            'fields' => [
                'ga4_id' => ['label' => 'Google Analytics 4 Measurement ID', 'type' => 'text', 'placeholder' => 'G-XXXXXXXXXX', 'help' => 'Analytics → Admin → Data streams → your site'],
                'gsc_verification' => ['label' => 'Google Search Console verification code', 'type' => 'text', 'placeholder' => 'content value of the google-site-verification meta tag'],
                'gtm_id' => ['label' => 'Google Tag Manager ID (optional)', 'type' => 'text', 'placeholder' => 'GTM-XXXXXXX'],
                'gads_id' => ['label' => 'Google Ads Conversion ID', 'type' => 'text', 'placeholder' => 'AW-123456789'],
                'gads_label' => ['label' => 'Google Ads Purchase conversion label', 'type' => 'text', 'placeholder' => 'AbCdEfGhIjk'],
                'gmc_id' => ['label' => 'Google Merchant Center ID', 'type' => 'text', 'help' => 'Add the product feed URL shown below in Merchant Center → Products → Feeds'],
                'meta_pixel_id' => ['label' => 'Meta (Facebook) Pixel ID', 'type' => 'text'],
                'meta_capi_token' => ['label' => 'Meta Conversions API access token', 'type' => 'secret', 'help' => 'Events Manager → your pixel → Settings → Conversions API → Generate access token'],
                'meta_domain_verification' => ['label' => 'Meta domain verification code', 'type' => 'text'],
                'pinterest_tag_id' => ['label' => 'Pinterest Tag ID', 'type' => 'text', 'help' => 'Pinterest Business → Ads → Conversions → Pinterest Tag'],
                'pinterest_verification' => ['label' => 'Pinterest site verification code', 'type' => 'text', 'placeholder' => 'content value of the p:domain_verify meta tag'],
                'bing_verification' => ['label' => 'Bing Webmaster verification code', 'type' => 'text'],
                'whatsapp_number' => ['label' => 'WhatsApp number (with country code, digits only)', 'type' => 'text', 'default' => '917710970512'],
                'whatsapp_message' => ['label' => 'WhatsApp default message', 'type' => 'text', 'default' => 'Hi! I would like to know more about your gift boxes.'],
                'header_code' => ['label' => 'Custom code in <head> (any extra tracking script)', 'type' => 'code'],
                'footer_code' => ['label' => 'Custom code before </body>', 'type' => 'code'],
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
