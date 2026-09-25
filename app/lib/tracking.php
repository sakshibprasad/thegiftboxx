<?php
declare(strict_types=1);

/**
 * Tracking scripts (GA4, Google Ads, GTM, Meta Pixel) driven by the IDs
 * entered in Admin → Settings → Google, Meta & more.
 * Ecommerce events are queued with track() and printed by the layout.
 */
function track(string $event, array $data = []): void
{
    $GLOBALS['__track'][] = ['event' => $event, 'data' => $data];
}

/** Queue an event that should fire on the next page view (after a redirect). */
function track_next(string $event, array $data = []): void
{
    $_SESSION['_track'][] = ['event' => $event, 'data' => $data];
}

function tracking_head(): string
{
    $out = '';
    $ga = trim((string) setting('ga4_id'));
    $ads = trim((string) setting('gads_id'));
    $gtm = trim((string) setting('gtm_id'));
    $pixel = trim((string) setting('meta_pixel_id'));

    if ($gtm && preg_match('/^GTM-[A-Z0-9]+$/', $gtm)) {
        $out .= "<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src='https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);})(window,document,'script','dataLayer','" . e($gtm) . "');</script>\n";
    }
    $firstTag = $ga ?: $ads;
    if ($firstTag) {
        $out .= '<script async src="https://www.googletagmanager.com/gtag/js?id=' . e($firstTag) . '"></script>' . "\n";
        $out .= "<script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}gtag('js',new Date());";
        if ($ga) {
            $out .= "gtag('config'," . json_encode($ga) . ');';
        }
        if ($ads) {
            $out .= "gtag('config'," . json_encode($ads) . ');';
        }
        $out .= "</script>\n";
    }
    if ($pixel && ctype_digit($pixel)) {
        $out .= "<script>!function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,document,'script','https://connect.facebook.net/en_US/fbevents.js');fbq('init','" . e($pixel) . "');fbq('track','PageView');</script>\n";
    }
    $pin = trim((string) setting('pinterest_tag_id'));
    if ($pin && ctype_digit($pin)) {
        $out .= "<script>!function(e){if(!window.pintrk){window.pintrk=function(){window.pintrk.queue.push(Array.prototype.slice.call(arguments))};var n=window.pintrk;n.queue=[],n.version='3.0';var t=document.createElement('script');t.async=!0,t.src=e;var r=document.getElementsByTagName('script')[0];r.parentNode.insertBefore(t,r)}}('https://s.pinimg.com/ct/core.js');pintrk('load','" . e($pin) . "');pintrk('page');</script>\n";
    }
    return $out . (string) setting('header_code');
}

function tracking_body_end(): string
{
    $events = array_merge($_SESSION['_track'] ?? [], $GLOBALS['__track'] ?? []);
    unset($_SESSION['_track']);
    $out = '';
    if ($events) {
        $out .= '<script>window.TGB_EVENTS=' . json_encode($events, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) . ';</script>' . "\n";
    }
    $out .= '<script>window.TGB_TRACK=' . json_encode([
        'ads' => setting('gads_id') && setting('gads_label') ? setting('gads_id') . '/' . setting('gads_label') : null,
    ]) . ';</script>' . "\n";
    return $out . (string) setting('footer_code');
}

/** GA4-style item for a product/cart line. */
function track_item(array $p, ?array $v = null, int $qty = 1, ?float $price = null): array
{
    return array_filter([
        'item_id' => $v ? 'TGB-' . $p['id'] . '-' . $v['id'] : 'TGB-' . $p['id'],
        'item_name' => $p['name'],
        'item_variant' => $v ? variation_label(json_arr($v['attributes_json'])) : null,
        'price' => $price,
        'quantity' => $qty,
    ], fn($x) => $x !== null);
}
