<?php

namespace Vitebox\LaravelBlog\PostTypes;

/** 5) News feed item */
class NewsPost extends PostType
{
    public function label(): string
    {
        return 'News Feed';
    }

    public function description(): string
    {
        return 'Short, timely news item with source attribution and a "breaking" flag.';
    }

    public function icon(): string
    {
        return '🗞️';
    }

    public function fields(): array
    {
        return [
            Field::checkbox('is_breaking', 'Breaking news'),
            Field::text('source_name', 'Source name'),
            Field::url('source_url', 'Source URL'),
            Field::text('location', 'Dateline / location')->placeholder('New Delhi'),
        ];
    }
}
