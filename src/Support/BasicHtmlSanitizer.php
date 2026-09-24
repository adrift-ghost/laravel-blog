<?php

namespace Vitebox\LaravelBlog\Support;

use DOMDocument;
use DOMElement;
use DOMNode;
use Vitebox\LaravelBlog\Contracts\HtmlSanitizer;

/**
 * Allow-list based HTML sanitizer with zero dependencies.
 *
 * Keeps common editorial markup, strips scripts, event handlers, style
 * attributes and javascript: URLs, and only allows iframes whose host is
 * listed in config('blog.content.allowed_iframe_hosts').
 *
 * For stricter needs, bind your own implementation (e.g. HTMLPurifier)
 * to Vitebox\LaravelBlog\Contracts\HtmlSanitizer.
 */
class BasicHtmlSanitizer implements HtmlSanitizer
{
    /** @var array<string, array<int, string>> tag => allowed attributes */
    protected array $allowed = [
        'p' => [], 'br' => [], 'hr' => [], 'span' => [], 'div' => [],
        'h1' => ['id'], 'h2' => ['id'], 'h3' => ['id'], 'h4' => ['id'], 'h5' => ['id'], 'h6' => ['id'],
        'strong' => [], 'b' => [], 'em' => [], 'i' => [], 'u' => [], 's' => [], 'del' => [], 'mark' => [], 'sub' => [], 'sup' => [], 'small' => [],
        'blockquote' => ['cite'], 'q' => ['cite'], 'cite' => [], 'code' => [], 'pre' => [], 'kbd' => [],
        'ul' => [], 'ol' => ['start'], 'li' => [], 'dl' => [], 'dt' => [], 'dd' => [],
        'a' => ['href', 'title', 'target', 'rel'],
        'img' => ['src', 'alt', 'title', 'width', 'height', 'loading'],
        'figure' => [], 'figcaption' => [],
        'table' => [], 'thead' => [], 'tbody' => [], 'tfoot' => [], 'tr' => [], 'th' => ['colspan', 'rowspan', 'scope'], 'td' => ['colspan', 'rowspan'], 'caption' => [],
        'iframe' => ['src', 'width', 'height', 'allow', 'allowfullscreen', 'frameborder', 'title'],
        'video' => ['src', 'controls', 'poster', 'width', 'height'], 'source' => ['src', 'type'],
    ];

    /** Tags removed together with their content. */
    protected array $drop = ['script', 'style', 'object', 'embed', 'form', 'input', 'button', 'select', 'textarea', 'link', 'meta', 'base', 'svg', 'math', 'template', 'noscript'];

    protected array $urlAttributes = ['href', 'src', 'cite', 'poster'];

    public function sanitize(string $html): string
    {
        if (trim($html) === '') {
            return '';
        }

        $doc = new DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);
        $doc->loadHTML('<?xml encoding="UTF-8"?><div id="__blog_root">'.$html.'</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $root = $doc->getElementById('__blog_root');
        if (! $root) {
            return e(strip_tags($html));
        }

        $this->cleanChildren($root);

        $out = '';
        foreach (iterator_to_array($root->childNodes) as $child) {
            $out .= $doc->saveHTML($child);
        }

        return trim($out);
    }

    protected function cleanChildren(DOMNode $node): void
    {
        foreach (iterator_to_array($node->childNodes) as $child) {
            if ($child instanceof DOMElement) {
                $this->cleanElement($child);
            } elseif ($child->nodeType === XML_COMMENT_NODE || $child->nodeType === XML_PI_NODE) {
                $node->removeChild($child);
            }
        }
    }

    protected function cleanElement(DOMElement $el): void
    {
        $tag = strtolower($el->tagName);

        if (in_array($tag, $this->drop, true)) {
            $el->parentNode?->removeChild($el);

            return;
        }

        if (! array_key_exists($tag, $this->allowed)) {
            // unwrap: keep children, drop the element itself
            $this->cleanChildren($el);
            while ($el->firstChild) {
                $el->parentNode?->insertBefore($el->firstChild, $el);
            }
            $el->parentNode?->removeChild($el);

            return;
        }

        foreach (iterator_to_array($el->attributes) as $attr) {
            $name = strtolower($attr->name);
            if (! in_array($name, $this->allowed[$tag], true)) {
                $el->removeAttribute($attr->name);

                continue;
            }
            if (in_array($name, $this->urlAttributes, true) && ! $this->isSafeUrl($attr->value)) {
                $el->removeAttribute($attr->name);
            }
        }

        if ($tag === 'iframe' && ! $this->isAllowedIframe($el->getAttribute('src'))) {
            $el->parentNode?->removeChild($el);

            return;
        }

        if ($tag === 'a' && $el->getAttribute('target') === '_blank') {
            $el->setAttribute('rel', 'noopener noreferrer');
        }

        $this->cleanChildren($el);
    }

    protected function isSafeUrl(string $url): bool
    {
        $url = trim(preg_replace('/[\x00-\x20]+/', '', $url) ?? '');
        if ($url === '' || str_starts_with($url, '#') || str_starts_with($url, '/')) {
            return true;
        }

        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));

        return $scheme === '' ? ! str_contains($url, ':') : in_array($scheme, ['http', 'https', 'mailto', 'tel'], true);
    }

    protected function isAllowedIframe(string $src): bool
    {
        $host = strtolower((string) parse_url($src, PHP_URL_HOST));
        $scheme = strtolower((string) parse_url($src, PHP_URL_SCHEME));

        return $scheme === 'https' && in_array($host, (array) config('blog.content.allowed_iframe_hosts', []), true);
    }
}
