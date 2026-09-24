<?php

namespace Vitebox\LaravelBlog\PostTypes;

/** 1) Cover image + content */
class StandardPost extends PostType
{
    public function label(): string
    {
        return 'Cover Image + Content';
    }

    public function description(): string
    {
        return 'A classic blog post: a cover image followed by rich content.';
    }

    public function icon(): string
    {
        return '🖼️';
    }

    public function coverImage(): string
    {
        return self::REQUIRED;
    }
}
