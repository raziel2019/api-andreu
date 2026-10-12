<?php

namespace App\Services;

use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;
use Symfony\Component\HtmlSanitizer\Visitor\AttributeSanitizer\AttributeSanitizerInterface;

class RichTextLinkSanitizer implements AttributeSanitizerInterface
{
    public function getSupportedElements(): ?array
    {
        return ['a'];
    }

    public function getSupportedAttributes(): ?array
    {
        return ['href'];
    }

    public function sanitizeAttribute(string $element, string $attribute, string $value, HtmlSanitizerConfig $config): ?string
    {
        if (preg_match('/[\x00-\x20\x7f\\\\]/', $value) || str_starts_with($value, '//')) {
            return null;
        }
        if (preg_match('~^https?://~i', $value)) {
            return filter_var($value, FILTER_VALIDATE_URL) && parse_url($value, PHP_URL_USER) === null && parse_url($value, PHP_URL_PASS) === null ? $value : null;
        }
        if (preg_match('~^mailto:[^\s@]+@[^\s@]+~i', $value)) {
            return $value;
        }

        return preg_match('~^(?:/(?!/)|\#|\?|\.?\.?/|[a-z0-9_-]+(?:/|\.|$))~i', $value) && ! preg_match('~^[^/?\#]*:~', $value) ? $value : null;
    }
}
