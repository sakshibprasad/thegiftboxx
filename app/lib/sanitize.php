<?php
declare(strict_types=1);

/**
 * Allow-list HTML sanitizer for rich text written in the admin
 * (product descriptions, pages). Strips scripts, styles and event handlers.
 */
function clean_html(?string $html): string
{
    $html = trim((string) $html);
    if ($html === '') {
        return '';
    }
    $allowed = [
        'p' => [], 'br' => [], 'strong' => [], 'b' => [], 'em' => [], 'i' => [], 'u' => [], 's' => [],
        'h2' => [], 'h3' => [], 'h4' => [], 'ul' => [], 'ol' => [], 'li' => [], 'blockquote' => [],
        'a' => ['href', 'target', 'rel'], 'img' => ['src', 'alt', 'width', 'height'], 'hr' => [],
        'table' => [], 'thead' => [], 'tbody' => [], 'tr' => [], 'th' => [], 'td' => [], 'span' => [], 'div' => [],
    ];
    $doc = new DOMDocument();
    libxml_use_internal_errors(true);
    $doc->loadHTML('<?xml encoding="utf-8"?><div id="__root">' . $html . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    libxml_clear_errors();
    $root = $doc->getElementById('__root');
    if (!$root) {
        return e(strip_tags($html));
    }
    clean_node($root, $allowed);
    $out = '';
    foreach (iterator_to_array($root->childNodes) as $child) {
        $out .= $doc->saveHTML($child);
    }
    return trim($out);
}

function clean_node(DOMNode $node, array $allowed): void
{
    foreach (iterator_to_array($node->childNodes) as $child) {
        if ($child instanceof DOMElement) {
            $tag = strtolower($child->tagName);
            if (in_array($tag, ['script', 'style', 'iframe', 'object', 'embed', 'form', 'input', 'button', 'svg', 'math'], true)) {
                $node->removeChild($child);
                continue;
            }
            if (!isset($allowed[$tag])) {
                clean_node($child, $allowed);
                while ($child->firstChild) {
                    $node->insertBefore($child->firstChild, $child);
                }
                $node->removeChild($child);
                continue;
            }
            foreach (iterator_to_array($child->attributes) as $attr) {
                $name = strtolower($attr->name);
                if (!in_array($name, $allowed[$tag], true)) {
                    $child->removeAttribute($attr->name);
                    continue;
                }
                if (in_array($name, ['href', 'src'], true)) {
                    $v = trim($attr->value);
                    if (!preg_match('#^(https?://|/|mailto:|tel:|\#)#i', $v)) {
                        $child->removeAttribute($attr->name);
                    }
                }
            }
            if ($tag === 'a' && $child->getAttribute('target') === '_blank') {
                $child->setAttribute('rel', 'noopener');
            }
            clean_node($child, $allowed);
        } elseif ($child instanceof DOMComment) {
            $node->removeChild($child);
        }
    }
}
