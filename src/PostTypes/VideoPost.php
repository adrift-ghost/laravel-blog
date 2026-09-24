<?php

namespace Vitebox\LaravelBlog\PostTypes;

use Vitebox\LaravelBlog\Models\Post;

/** 2) Cover image + video + content */
class VideoPost extends PostType
{
    public function label(): string
    {
        return 'Cover Image + Video + Content';
    }

    public function description(): string
    {
        return 'Cover image, an embedded (YouTube / Vimeo) or uploaded video, and content.';
    }

    public function icon(): string
    {
        return '🎬';
    }

    public function coverImage(): string
    {
        return self::REQUIRED;
    }

    public function video(): string
    {
        return self::REQUIRED;
    }

    public function fields(): array
    {
        return [
            Field::text('duration', 'Duration')->placeholder('e.g. 12:45'),
            Field::textarea('transcript', 'Transcript')->help('Improves accessibility and SEO.'),
        ];
    }

    public function present(Post $post): array
    {
        return parent::present($post) + ['video' => $post->video];
    }
}
