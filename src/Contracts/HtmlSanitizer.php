<?php

namespace Vitebox\LaravelBlog\Contracts;

interface HtmlSanitizer
{
    public function sanitize(string $html): string;
}
