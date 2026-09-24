<?php

namespace Vitebox\LaravelBlog\PostTypes;

use Vitebox\LaravelBlog\Models\Post;

/** 3) Event post */
class EventPost extends PostType
{
    public function label(): string
    {
        return 'Event';
    }

    public function description(): string
    {
        return 'An event with dates, venue or online link, and registration details.';
    }

    public function icon(): string
    {
        return '📅';
    }

    public function fields(): array
    {
        return [
            Field::datetime('starts_at', 'Starts at')->column('starts_at')->required(),
            Field::datetime('ends_at', 'Ends at')->column('ends_at')->rules(['after_or_equal:fields.starts_at']),
            Field::checkbox('is_online', 'Online event'),
            Field::text('venue', 'Venue')->placeholder('Hall A, Convention Centre'),
            Field::textarea('address', 'Address'),
            Field::url('online_url', 'Online meeting / stream URL'),
            Field::url('registration_url', 'Registration URL'),
            Field::text('organizer', 'Organizer'),
            Field::text('price', 'Price')->placeholder('Free / ₹499'),
            Field::number('capacity', 'Capacity')->rules(['integer', 'min:0']),
        ];
    }

    public function present(Post $post): array
    {
        $now = now();
        $state = match (true) {
            ! $post->starts_at => null,
            $post->starts_at->isFuture() => 'upcoming',
            $post->ends_at && $post->ends_at->isPast() => 'past',
            ! $post->ends_at && $post->starts_at->lt($now->copy()->startOfDay()) => 'past',
            default => 'ongoing',
        };

        return parent::present($post) + ['event_state' => $state];
    }
}
