<?php
declare(strict_types=1);

/** Homepage content — edited in Admin → Homepage. These are the starting values. */
function home_defaults(): array
{
    return [
        'hero_eyebrow' => 'Premium gift hampers',
        'hero_title' => 'The art of gifting, beautifully boxed',
        'hero_text' => 'Premium wooden gift boxes filled with branded favourites, curated with love for birthdays, weddings, festivals and every moment worth celebrating.',
        'hero_button' => 'Explore gift boxes',
        'hero_link' => '/shop/',
        'hero_image' => '',
        'hero_image_2' => '',
        'marquee' => 'Birthdays · Anniversaries · Weddings · Diwali · Corporate gifting · Wellness · Just because',
        'intro_eyebrow' => 'Our story',
        'intro_title' => 'Gifts that are kept, not just opened',
        'intro_text' => "Some gifts are unwrapped and forgotten. Ours are kept. Every Gift Boxx arrives in a premium wooden box, filled with branded favourites chosen for the person and the moment, and presented so beautifully it needs no wrapping.\n\nYou tell us the occasion. We take care of everything else.",
        'intro_image' => '',
        'features' => [
            ['title' => 'Premium wooden boxes', 'text' => 'Crafted in pine, teak or ply. Made to be kept, reused and remembered.'],
            ['title' => 'Branded favourites inside', 'text' => 'Only brands we trust and products we would love to receive ourselves.'],
            ['title' => 'Ready to gift', 'text' => 'Arrives beautifully presented. No wrapping, no running around.'],
            ['title' => 'Personal touches', 'text' => 'Add a gift message and choose a delivery date at checkout.'],
        ],
        'collections_title' => 'Shop by occasion',
        'collections_text' => 'Birthdays, weddings, festivals and everything in between. Pick the moment and we will handle the rest.',
        'boxes_title' => 'Choose your wood',
        'boxes_text' => 'Every box is a keepsake. Pick the finish that suits the moment.',
        'popular_title' => 'Most loved boxes',
        'popular_text' => 'The boxes our customers keep coming back for.',
        'promo_eyebrow' => 'Limited edition',
        'promo_title' => 'Festive Gift Boxes',
        'promo_text' => 'Celebrate the season with premium festive hampers in wooden boxes, for family, friends and everyone who made the year special.',
        'promo_button' => 'Shop festive boxes',
        'promo_link' => '/product-category/festive-gift-boxes/',
        'promo_image' => '',
        'reviews_title' => 'Kind words',
        'custom_title' => 'Have something specific in mind?',
        'custom_text' => 'Tell us who it is for, your budget and a few ideas. We will design a box around it and get back to you within a day.',
        'corporate_title' => 'Corporate & bulk gifting',
        'corporate_text' => 'Diwali, onboarding kits, client thank-yous. Premium branded wooden boxes for teams of every size.',
        'faq_title' => 'Questions, answered',
        'faq' => [
            ['q' => 'What comes inside a gift box?', 'a' => 'Every box is filled with branded, premium products chosen for the occasion, from gourmet treats and fragrances to keepsakes. Each product page lists exactly what is inside.'],
            ['q' => 'What is the difference between pinewood, teakwood and plywood boxes?', 'a' => 'Pinewood is light with a soft natural finish. Teakwood is richer, darker and heavier, our most premium option. Plywood is sturdy and elegant. All three are made to be kept and reused.'],
            ['q' => 'Can I add a personal message?', 'a' => 'Yes. Add your message at checkout and we will include it on a card inside the box.'],
            ['q' => 'How long does delivery take?', 'a' => 'Boxes are packed to order and delivered across India. You can choose a preferred delivery date at checkout, from one week onwards.'],
            ['q' => 'Do you do custom or bulk orders?', 'a' => 'We do. Use our custom box or corporate gifting form and we will reply within one working day.'],
        ],
        // Section switches ('1' = show)
        'show_marquee' => '1', 'show_story' => '1', 'show_collections' => '1', 'show_boxes' => '1', 'show_popular' => '1',
        'show_promo' => '1', 'show_reviews' => '1', 'show_split' => '1', 'show_faq' => '1', 'show_journal' => '1',
    ];
}

/** Homepage sections that can be switched on/off in Admin → Homepage. */
function home_sections(): array
{
    return ['show_marquee' => 'Scrolling occasions ribbon', 'show_story' => 'Our story (image + highlights)', 'show_collections' => 'Shop by occasion',
        'show_boxes' => 'Choose your wood (box types)', 'show_popular' => 'Most loved boxes', 'show_promo' => 'Feature banner (festive)',
        'show_reviews' => 'Customer reviews', 'show_split' => 'Custom box & corporate cards', 'show_faq' => 'FAQ', 'show_journal' => 'Latest blog posts'];
}

function home_content(): array
{
    $saved = setting_json('home_content');
    $out = array_replace(home_defaults(), array_filter($saved, fn($v) => $v !== '' && $v !== []));
    foreach (array_keys(home_sections()) as $k) {
        if (isset($saved[$k])) {
            $out[$k] = $saved[$k]; // '0' must be respected
        }
    }
    return $out;
}

/** Default CMS pages created by the installer. */
function default_pages(): array
{
    $pages = [
        'about' => ['About Us', '<h2>About The Gift Boxx</h2><p>The Gift Boxx started with a simple thought: gifting should feel personal, not complicated.</p><p>We’ve all been there. You want to give something meaningful, but you end up stressed, short on time, or unsure what to choose. That’s exactly the problem we set out to solve.</p><p>We curate gift boxes that feel thoughtful and premium without you spending hours figuring it out. Birthday, anniversary, celebration, or just a small gesture to show you care, we help you make it memorable.</p><h3>What makes our boxes different</h3><ul><li><strong>Premium wooden boxes.</strong> Crafted in pinewood, teakwood or plywood. Made to be kept.</li><li><strong>Ready to gift.</strong> No extra wrapping needed. It arrives beautifully presented.</li><li><strong>Branded favourites inside.</strong> Premium products from brands we trust.</li><li><strong>Curated with love.</strong> Every box is put together for the person and the moment.</li></ul>'],
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
