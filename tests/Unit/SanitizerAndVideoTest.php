<?php

namespace Vitebox\LaravelBlog\Tests\Unit;

use Vitebox\LaravelBlog\Support\BasicHtmlSanitizer;
use Vitebox\LaravelBlog\Support\VideoEmbed;
use Vitebox\LaravelBlog\Tests\TestCase;

class SanitizerAndVideoTest extends TestCase
{
    public function test_it_strips_dangerous_markup(): void
    {
        $clean = (new BasicHtmlSanitizer)->sanitize(
            '<h2 onclick="x()">Hi</h2><script>alert(1)</script><p style="color:red">Text <a href="javascript:alert(1)">bad</a> <a href="https://ok.test" target="_blank">ok</a></p>'
            .'<iframe src="https://evil.test/x"></iframe><iframe src="https://www.youtube.com/embed/dQw4w9WgXcQ"></iframe><custom>kept text</custom>'
        );

        $this->assertStringNotContainsString('script', $clean);
        $this->assertStringNotContainsString('onclick', $clean);
        $this->assertStringNotContainsString('javascript:', $clean);
        $this->assertStringNotContainsString('style=', $clean);
        $this->assertStringNotContainsString('evil.test', $clean);
        $this->assertStringContainsString('youtube.com/embed', $clean);
        $this->assertStringContainsString('rel="noopener noreferrer"', $clean);
        $this->assertStringContainsString('kept text', $clean);
        $this->assertStringContainsString('<h2>Hi</h2>', $clean);
    }

    public function test_it_keeps_unicode(): void
    {
        $this->assertSame('<p>नमस्ते — ✓</p>', (new BasicHtmlSanitizer)->sanitize('<p>नमस्ते — ✓</p>'));
    }

    public function test_video_urls_are_parsed(): void
    {
        $this->assertSame('dQw4w9WgXcQ', VideoEmbed::parse('https://youtu.be/dQw4w9WgXcQ')['id']);
        $this->assertSame('youtube', VideoEmbed::parse('https://www.youtube.com/watch?v=dQw4w9WgXcQ&t=1')['provider']);
        $this->assertSame('https://player.vimeo.com/video/76979871', VideoEmbed::parse('https://vimeo.com/76979871')['embed_url']);
        $this->assertSame('file', VideoEmbed::parse('https://cdn.test/a.mp4')['provider']);
        $this->assertNull(VideoEmbed::parse(null));
    }
}
