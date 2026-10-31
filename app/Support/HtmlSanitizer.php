<?php

namespace App\Support;

use DOMDocument;
use DOMElement;
use DOMNode;

/**
 * Allow-list HTML sanitizer for rich text written by editors (news content).
 *
 * Only a small set of formatting tags and attributes is kept. Unknown tags are
 * unwrapped (their text is kept), dangerous containers are dropped with their
 * content, and link/image URLs must be relative or use http, https or mailto.
 */
class HtmlSanitizer
{
    /** Tags kept as-is, with the attributes allowed on each. */
    private const ALLOWED = [
        'p' => [], 'br' => [], 'hr' => [], 'div' => [], 'span' => [],
        'h1' => [], 'h2' => [], 'h3' => [], 'h4' => [], 'h5' => [], 'h6' => [],
        'strong' => [], 'b' => [], 'em' => [], 'i' => [], 'u' => [], 's' => [],
        'small' => [], 'sub' => [], 'sup' => [], 'mark' => [],
        'ul' => [], 'ol' => [], 'li' => [],
        'blockquote' => [], 'pre' => [], 'code' => [],
        'table' => [], 'thead' => [], 'tbody' => [], 'tr' => [], 'th' => [], 'td' => [],
        'a' => ['href', 'title'],
        'img' => ['src', 'alt', 'title', 'width', 'height'],
    ];

    /** Tags removed together with everything inside them. */
    private const DROPPED = [
        'script', 'style', 'iframe', 'frame', 'frameset', 'object', 'embed', 'applet',
        'noscript', 'noembed', 'noframes', 'template', 'textarea', 'title', 'xmp',
        'plaintext', 'svg', 'math', 'form', 'input', 'button', 'select', 'option',
        'link', 'meta', 'base', 'head',
    ];

    private const URL_ATTRIBUTES = ['href', 'src'];

    private const ALLOWED_SCHEMES = ['http', 'https', 'mailto'];

    public static function clean(?string $html): string
    {
        if ($html === null || trim($html) === '') {
            return '';
        }

        $document = new DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);
        // The XML encoding hint makes libxml read the input as UTF-8.
        $document->loadHTML(
            '<?xml encoding="UTF-8"?><div id="__sanitizer_root">' . $html . '</div>',
            LIBXML_NONET | LIBXML_HTML_NODEFDTD | LIBXML_HTML_NOIMPLIED
        );
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $root = $document->getElementById('__sanitizer_root');
        if ($root === null) {
            return '';
        }

        self::cleanChildren($root);

        $output = '';
        foreach ($root->childNodes as $child) {
            $output .= $document->saveHTML($child);
        }

        return $output;
    }

    private static function cleanChildren(DOMNode $parent): void
    {
        // Copy the list first: the loop changes the tree.
        foreach (iterator_to_array($parent->childNodes) as $node) {
            switch ($node->nodeType) {
                case XML_TEXT_NODE:
                    break;
                case XML_ELEMENT_NODE:
                    self::cleanElement($node);
                    break;
                default:
                    // Comments, CDATA, processing instructions...
                    $parent->removeChild($node);
            }
        }
    }

    private static function cleanElement(DOMElement $element): void
    {
        $tag = strtolower($element->nodeName);
        $parent = $element->parentNode;

        if (in_array($tag, self::DROPPED, true)) {
            $parent->removeChild($element);

            return;
        }

        self::cleanChildren($element);

        if (! array_key_exists($tag, self::ALLOWED)) {
            // Unwrap: keep the (already cleaned) children, drop the tag.
            while ($element->firstChild) {
                $parent->insertBefore($element->firstChild, $element);
            }
            $parent->removeChild($element);

            return;
        }

        foreach (iterator_to_array($element->attributes) as $attribute) {
            $name = strtolower($attribute->nodeName);
            $keep = in_array($name, self::ALLOWED[$tag], true)
                && (! in_array($name, self::URL_ATTRIBUTES, true) || self::isSafeUrl($attribute->nodeValue));

            if (! $keep) {
                $element->removeAttributeNode($attribute);
            }
        }

        if ($tag === 'a' && $element->hasAttribute('href')) {
            $element->setAttribute('rel', 'noopener noreferrer nofollow');
        }
    }

    private static function isSafeUrl(?string $url): bool
    {
        // Browsers ignore control characters and whitespace inside the scheme.
        $normalized = preg_replace('/[\x00-\x20\x7F]+/', '', (string) $url);

        if ($normalized === '') {
            return false;
        }

        // Protocol-relative URLs are fine (same scheme as the page).
        if (! preg_match('/^([a-z][a-z0-9+.\-]*):/i', $normalized, $matches)) {
            return true;
        }

        return in_array(strtolower($matches[1]), self::ALLOWED_SCHEMES, true);
    }
}
