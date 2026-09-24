<?php

namespace Vitebox\LaravelBlog\Enums;

enum PostStatus: string
{
    case Draft = 'draft';
    case PendingReview = 'pending_review';
    case ChangesRequested = 'changes_requested';
    case Approved = 'approved';
    case Scheduled = 'scheduled';
    case Published = 'published';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::PendingReview => 'Pending review',
            self::ChangesRequested => 'Changes requested',
            self::Approved => 'Approved',
            self::Scheduled => 'Scheduled',
            self::Published => 'Published',
            self::Archived => 'Archived',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft => 'gray',
            self::PendingReview => 'amber',
            self::ChangesRequested => 'red',
            self::Approved => 'blue',
            self::Scheduled => 'violet',
            self::Published => 'green',
            self::Archived => 'slate',
        };
    }

    /** States in which the original author may still edit the post. */
    public function isEditableByAuthor(): bool
    {
        return in_array($this, [self::Draft, self::ChangesRequested], true);
    }

    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $s) => [$s->value => $s->label()])->all();
    }
}
