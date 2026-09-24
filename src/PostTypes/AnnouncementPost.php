<?php

namespace Vitebox\LaravelBlog\PostTypes;

use Vitebox\LaravelBlog\Models\Post;

/** 6) Public announcement */
class AnnouncementPost extends PostType
{
    public function label(): string
    {
        return 'Public Announcement';
    }

    public function description(): string
    {
        return 'Official notice with priority, audience, call-to-action and expiry date.';
    }

    public function icon(): string
    {
        return '📢';
    }

    public function fields(): array
    {
        return [
            Field::select('priority', 'Priority', [
                'low' => 'Low', 'normal' => 'Normal', 'high' => 'High', 'critical' => 'Critical',
            ])->required()->default('normal'),
            Field::text('audience', 'Audience')->placeholder('All customers'),
            Field::datetime('expires_at', 'Expires at')->column('expires_at')->help('Hidden from "active announcements" after this date.'),
            Field::checkbox('show_banner', 'Show as site-wide banner'),
            Field::text('cta_label', 'Button label'),
            Field::url('cta_url', 'Button URL'),
        ];
    }

    public function present(Post $post): array
    {
        return parent::present($post) + [
            'is_active' => ! $post->expires_at || $post->expires_at->isFuture(),
        ];
    }
}
