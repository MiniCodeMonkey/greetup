<?php

namespace App\Services;

use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\DisallowedRawHtml\DisallowedRawHtmlExtension;
use League\CommonMark\Extension\ExternalLink\ExternalLinkExtension;
use League\CommonMark\MarkdownConverter;

class MarkdownService
{
    private MarkdownConverter $converter;

    public function __construct()
    {
        $environment = new Environment([
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
            'external_link' => [
                'internal_hosts' => [],
                'open_in_new_window' => true,
                'html_class' => '',
                'nofollow' => 'all',
                'noopener' => 'all',
            ],
            'disallowed_raw_html' => [
                'disallowed_tags' => [
                    'title', 'textarea', 'style', 'xmp',
                    'noembed', 'noframes', 'script', 'plaintext',
                    'iframe', 'object', 'embed',
                ],
            ],
        ]);

        $environment->addExtension(new CommonMarkCoreExtension);
        $environment->addExtension(new DisallowedRawHtmlExtension);
        $environment->addExtension(new ExternalLinkExtension);

        $this->converter = new MarkdownConverter($environment);
    }

    public function render(?string $markdown): string
    {
        if ($markdown === null || trim($markdown) === '') {
            return '';
        }

        return trim($this->converter->convert($markdown)->getContent());
    }
}
