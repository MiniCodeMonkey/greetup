<?php

use App\Services\MarkdownService;

beforeEach(function () {
    $this->service = new MarkdownService;
});

it('renders headings', function () {
    expect($this->service->render('# Hello'))
        ->toBe('<h1>Hello</h1>');
});

it('renders unordered lists', function () {
    $markdown = "- Item 1\n- Item 2\n- Item 3";

    $html = $this->service->render($markdown);

    expect($html)->toContain('<ul>');
    expect($html)->toContain('<li>Item 1</li>');
    expect($html)->toContain('<li>Item 2</li>');
    expect($html)->toContain('<li>Item 3</li>');
});

it('renders links with nofollow noopener and target blank', function () {
    $html = $this->service->render('[Example](https://example.com)');

    expect($html)->toContain('nofollow');
    expect($html)->toContain('noopener');
    expect($html)->toContain('target="_blank"');
    expect($html)->toContain('href="https://example.com"');
});

it('renders code blocks', function () {
    $markdown = "```php\necho 'hello';\n```";

    $html = $this->service->render($markdown);

    expect($html)->toContain('<pre>');
    expect($html)->toContain('<code');
});

it('strips script tags from raw HTML', function () {
    $html = $this->service->render('<script>alert("xss")</script>');

    expect($html)->not->toContain('<script>');
    expect($html)->not->toContain('&lt;script&gt;');
});

it('strips iframe tags from raw HTML', function () {
    $html = $this->service->render('<iframe src="https://evil.com"></iframe>');

    expect($html)->not->toContain('<iframe');
    expect($html)->not->toContain('&lt;iframe');
});

it('strips object and embed tags from raw HTML', function () {
    $html = $this->service->render('<object data="x"></object><embed src="y">');

    expect($html)->not->toContain('<object');
    expect($html)->not->toContain('<embed');
});

it('returns empty string for null input', function () {
    expect($this->service->render(null))->toBe('');
});

it('returns empty string for empty string input', function () {
    expect($this->service->render(''))->toBe('');
});

it('returns empty string for whitespace-only input', function () {
    expect($this->service->render('   '))->toBe('');
});
