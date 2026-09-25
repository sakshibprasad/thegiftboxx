<?php
declare(strict_types=1);

/** Homepage content — edited in Admin → Homepage. These are the starting values. */
function home_defaults(): array
{
    return [
        'hero_eyebrow' => 'Premium gift hampers · Made in India',
        'hero_title' => 'Gifts that feel personal, in boxes worth keeping',
        'hero_text' => 'Hand-picked gift hampers packed in real wooden boxes. Birthdays, anniversaries, Valentine’s, weddings, or just because.',
        'hero_button' => 'Explore gift boxes',
        'hero_link' => '/shop/',
        'hero_image' => '',
        'hero_image_2' => '',
        'intro_title' => 'We put the thought in, so you don’t have to',
        'intro_text' => "Most of us know the feeling. You want to give something meaningful, but time is short and nothing feels quite right. That’s why we started The Gift Boxx.\n\nEvery box is packed with 6–10 good-quality, often branded, items and arrives in a reusable pine, teak or plywood box. No extra wrapping needed. It’s ready to hand over the moment it reaches you.",
        'features' => [
            ['title' => 'Real wooden boxes', 'text' => 'Pinewood, teakwood or plywood. Sturdy enough to keep and reuse long after the gift.'],
            ['title' => 'Ready to gift', 'text' => 'Arrives beautifully packed. No wrapping, no running around.'],
            ['title' => 'Branded products inside', 'text' => 'We only pack things we’d be happy to receive ourselves.'],
            ['title' => '6–10 items per box', 'text' => 'A complete gift, curated around the occasion and the person.'],
        ],
        'collections_title' => 'Shop by occasion',
        'collections_text' => 'Birthdays, weddings, corporate gifting and self-care. Pick the moment and we’ll handle the rest.',
        'popular_title' => 'Most loved boxes',
        'popular_text' => 'The boxes our customers keep coming back for.',
        'promo_title' => 'Valentine’s Gift Boxes',
        'promo_text' => 'Say it with something they’ll actually keep. Curated boxes for him and for her, packed in wood and ready to gift.',
        'promo_button' => 'Shop Valentine’s',
        'promo_link' => '/product-category/valentines-gift-boxes/',
        'promo_image' => '',
        'custom_title' => 'Have something specific in mind?',
        'custom_text' => 'Tell us who it’s for, your budget and a few ideas. We’ll design a box around it and get back to you within a day.',
        'corporate_title' => 'Corporate & bulk gifting',
        'corporate_text' => 'Diwali, onboarding kits, client thank-yous. Branded wooden boxes for teams of 10 to 1,000.',
        'faq' => [
            ['q' => 'What comes inside a gift box?', 'a' => 'Each box has 6 to 10 curated items (think chocolates, candles, mugs, perfumes and keepsakes) chosen for the occasion. Every product page lists exactly what’s inside.'],
            ['q' => 'What’s the difference between pinewood, teakwood and plywood boxes?', 'a' => 'Pinewood is light with a soft natural finish. Teakwood is richer, darker and heavier, our most premium option. Plywood is sturdy and budget-friendly. All three are reusable.'],
            ['q' => 'Can I add a personal message?', 'a' => 'Yes. Add your note at checkout and we’ll handwrite it on a card inside the box.'],
            ['q' => 'How long does delivery take?', 'a' => 'Most orders ship in 2–3 days and arrive within 4–7 days across India. You can pick a preferred delivery date at checkout.'],
            ['q' => 'Do you do custom or bulk orders?', 'a' => 'We do. Use our custom box or corporate gifting form and we’ll reply within one working day.'],
        ],
    ];
}

function home_content(): array
{
    $saved = setting_json('home_content');
    return array_replace(home_defaults(), array_filter($saved, fn($v) => $v !== '' && $v !== []));
}

/** Default CMS pages created by the installer. */
function default_pages(): array
{
    $pages = [
        'about' => ['About Us', '<h2>About The Gift Boxx</h2><p>The Gift Boxx started with a simple thought: gifting should feel personal, not complicated.</p><p>We’ve all been there. You want to give something meaningful, but you end up stressed, short on time, or unsure what to choose. That’s exactly the problem we set out to solve.</p><p>We curate gift boxes that feel thoughtful and premium without you spending hours figuring it out. Birthday, anniversary, celebration, or just a small gesture to show you care, we help you make it memorable.</p><h3>What makes our boxes different</h3><ul><li><strong>Premium wooden box.</strong> Made from pinewood, teakwood or plywood. Reusable, durable and good-looking.</li><li><strong>Ready to gift.</strong> No extra wrapping needed. It arrives gift-ready.</li><li><strong>Branded products inside.</strong> Quality items from brands we trust.</li><li><strong>A complete gift.</strong> 6–10 items, curated for the occasion.</li></ul>'],
        'privacy-policy' => ['Privacy Policy', '<p>Paste your privacy policy here from Admin → Pages.</p>'],
        'terms-conditions' => ['Terms & Conditions', '<p>Paste your terms and conditions here from Admin → Pages.</p>'],
        'refund_returns' => ['Refund & Returns Policy', '<p>Paste your refund and returns policy here from Admin → Pages.</p>'],
        'shipping-policy' => ['Shipping Policy', '<p>Most orders ship within 2–3 working days and are delivered within 4–7 days across India. You will receive a tracking link by email as soon as your box is on its way.</p>'],
    ];
    // Policies copied from the old WordPress site (app/seed/pages/*.html).
    foreach ($pages as $slug => $page) {
        $file = APP_DIR . '/seed/pages/' . $slug . '.html';
        if (is_file($file)) {
            $pages[$slug][1] = (string) file_get_contents($file);
        }
    }
    return $pages;
}
