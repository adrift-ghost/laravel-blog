<?php

namespace Vitebox\LaravelBlog\PostTypes;

/** 4) Article post (long-form) */
class ArticlePost extends PostType
{
    public function label(): string
    {
        return 'Article';
    }

    public function description(): string
    {
        return 'Long-form article with subtitle, optional table of contents and references.';
    }

    public function icon(): string
    {
        return '📰';
    }

    public function fields(): array
    {
        return [
            Field::text('subtitle', 'Subtitle'),
            Field::text('byline', 'Byline override')->help('Leave empty to show the author name.'),
            Field::checkbox('show_toc', 'Show table of contents'),
            Field::textarea('references', 'References / sources')->help('One per line.'),
        ];
    }
}
