<?php

namespace App\Services;

use Illuminate\Support\Str;

class SupportTicketContentSanitizer
{
    /**
     * @var array<int, string>
     */
    protected array $allowedTags = [
        'p', 'br', 'strong', 'em', 'u', 's', 'blockquote',
        'ul', 'ol', 'li', 'h2', 'h3', 'h4', 'code', 'pre',
        'a', 'img', 'span',
    ];

    public function sanitize(string $content): string
    {
        $content = trim($content);

        if ($content === '') {
            return '';
        }

        if ($content === strip_tags($content)) {
            return nl2br(e($content));
        }

        $previous = libxml_use_internal_errors(true);

        $dom = new \DOMDocument('1.0', 'UTF-8');
        $wrapped = '<!DOCTYPE html><html><body>'.$content.'</body></html>';
        $dom->loadHTML($wrapped, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NOERROR | LIBXML_NOWARNING);

        $this->sanitizeNodeTree($dom, $dom->getElementsByTagName('body')->item(0));

        $html = '';
        $body = $dom->getElementsByTagName('body')->item(0);

        if ($body) {
            foreach ($body->childNodes as $child) {
                $html .= $dom->saveHTML($child) ?: '';
            }
        }

        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        return trim($html);
    }

    protected function sanitizeNodeTree(\DOMDocument $dom, ?\DOMNode $node): void
    {
        if (! $node) {
            return;
        }

        if ($node instanceof \DOMElement) {
            $tag = strtolower($node->tagName);

            if (! in_array($tag, $this->allowedTags, true)) {
                $this->replaceWithChildren($node);
                return;
            }

            $this->sanitizeAttributes($node, $tag);
        }

        for ($i = $node->childNodes->length - 1; $i >= 0; $i--) {
            $this->sanitizeNodeTree($dom, $node->childNodes->item($i));
        }
    }

    protected function sanitizeAttributes(\DOMElement $element, string $tag): void
    {
        $allowed = match ($tag) {
            'a' => ['href', 'target', 'rel'],
            'img' => ['src', 'alt', 'title', 'width', 'height'],
            default => [],
        };

        for ($i = $element->attributes->length - 1; $i >= 0; $i--) {
            $attribute = $element->attributes->item($i);

            if (! $attribute instanceof \DOMAttr) {
                continue;
            }

            $name = strtolower($attribute->name);

            if (Str::startsWith($name, 'on') || $name === 'style' || ! in_array($name, $allowed, true)) {
                $element->removeAttributeNode($attribute);
                continue;
            }

            $value = trim($attribute->value);

            if ($tag === 'a' && $name === 'href' && ! $this->isSafeLink($value)) {
                $element->removeAttributeNode($attribute);
                continue;
            }

            if ($tag === 'img' && $name === 'src' && ! $this->isSafeImageSrc($value)) {
                $element->removeAttributeNode($attribute);
                continue;
            }
        }

        if ($tag === 'a') {
            $target = $element->getAttribute('target');

            if ($target === '_blank') {
                $element->setAttribute('rel', 'noopener noreferrer nofollow');
            }
        }
    }

    protected function isSafeLink(string $value): bool
    {
        if ($value === '' || Str::startsWith($value, ['#', '/'])) {
            return true;
        }

        return (bool) preg_match('/^(https?:|mailto:|tel:)/i', $value);
    }

    protected function isSafeImageSrc(string $value): bool
    {
        if ($value === '' || Str::startsWith($value, '/')) {
            return true;
        }

        return (bool) preg_match('/^(https?:|data:image\/(png|jpe?g|gif|webp);base64,)/i', $value);
    }

    protected function replaceWithChildren(\DOMElement $element): void
    {
        $parent = $element->parentNode;

        if (! $parent) {
            return;
        }

        while ($element->firstChild) {
            $parent->insertBefore($element->firstChild, $element);
        }

        $parent->removeChild($element);
    }
}
