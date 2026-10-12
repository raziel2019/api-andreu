<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Model;
use Symfony\Component\HtmlSanitizer\HtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerAction;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;

class RichText
{
    public const ELEMENTS = ['p', 'br', 'strong', 'em', 'u', 'h2', 'h3', 'ul', 'ol', 'li', 'blockquote', 'a'];

    /** @var array<string, list<string>> */
    public const FIELDS = [
        'HomePage' => ['hero_content', 'cta_content', 'services_introduction', 'stories_introduction'],
        'HomeServiceSummary' => ['description'],
        'AboutPage' => ['introduction', 'working_method_introduction'],
        'AboutBenefit' => ['content'],
        'WorkingMethodStep' => ['content'],
        'ServicePage' => ['lead', 'description'],
        'Service' => ['description'],
        'ServiceFeature' => ['content'],
        'PortfolioPage' => ['introduction', 'callout_content', 'message'],
        'PortfolioProject' => ['description', 'body'],
        'ContactPage' => ['introduction', 'content', 'conversation'],
        'LegalPage' => ['body'],
        'SiteSetting' => ['callout_content', 'footer_description'],
    ];

    private HtmlSanitizer $sanitizer;

    public function __construct()
    {
        $config = (new HtmlSanitizerConfig)
            ->defaultAction(HtmlSanitizerAction::Drop)
            ->allowLinkSchemes(['http', 'https', 'mailto'])
            ->allowRelativeLinks()
            ->withMaxInputLength(500000)
            ->withAttributeSanitizer(new RichTextLinkSanitizer);
        foreach (['div', 'span', 'section', 'article', 'header', 'footer', 'main', 'font', 'h1', 'h4', 'h5', 'h6', 'table', 'thead', 'tbody', 'tr', 'td', 'th'] as $element) {
            $config = $config->blockElement($element);
        }
        foreach (self::ELEMENTS as $element) {
            $config = $config->allowElement($element, $element === 'a' ? ['href'] : []);
        }
        foreach (['script', 'style', 'iframe', 'object', 'embed', 'svg', 'math', 'template', 'form', 'input', 'textarea', 'button', 'img', 'video', 'audio', 'noscript'] as $element) {
            $config = $config->dropElement($element);
        }
        $this->sanitizer = new HtmlSanitizer($config);
    }

    public static function isHtml(string $value): bool
    {
        return preg_match('/<\/?[a-z!][^>]*>/i', $value) === 1;
    }

    public function sanitize(string $value): string
    {
        $value = preg_replace(['~<(\/?)b(?=[\s>])~i', '~<(\/?)i(?=[\s>])~i'], ['<$1strong', '<$1em'], $value);

        return str_replace('<br />', '<br>', $this->sanitizer->sanitize($value));
    }

    public function html(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return $value;
        }
        if (self::isHtml($value)) {
            return $this->sanitize($value);
        }
        $paragraphs = preg_split('/\n\s*\n/', str_replace(["\r\n", "\r"], "\n", $value));

        return implode('', array_map(fn (string $paragraph): string => '<p>'.str_replace("\n", '<br>', htmlspecialchars($paragraph, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')).'</p>', $paragraphs));
    }

    public function text(?string $value): ?string
    {
        if ($value === null || ! self::isHtml($value)) {
            return $value;
        }
        $html = $this->sanitize($value);
        $html = preg_replace('~<br\s*/?>~i', "\n", $html);
        $html = preg_replace('~</(?:p|h2|h3|ul|ol|blockquote)>~i', "\n\n", $html);
        $html = preg_replace('~</li>~i', "\n", $html);
        $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return trim(preg_replace('/\n{3,}/', "\n\n", $text));
    }

    /** @return list<string> */
    public static function fields(Model $model): array
    {
        return self::FIELDS[class_basename($model)] ?? [];
    }
}
