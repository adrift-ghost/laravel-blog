<?php

namespace Vitebox\LaravelBlog\Events;

use Vitebox\LaravelBlog\Enums\PostStatus;
use Vitebox\LaravelBlog\Models\Post;

abstract class PostEvent
{
    public function __construct(
        public Post $post,
        public ?PostStatus $from = null,
        public ?PostStatus $to = null,
        public mixed $user = null,
        public ?string $comment = null,
    ) {
    }
}
