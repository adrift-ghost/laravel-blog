<?php

namespace Vitebox\LaravelBlog\Enums;

enum BlogRole: string
{
    case Admin = 'admin';
    case Publisher = 'publisher';
    case Reviewer = 'reviewer';
    case Writer = 'writer';

    public function label(): string
    {
        return ucfirst($this->value);
    }

    public function description(): string
    {
        return match ($this) {
            self::Admin => 'Full control: settings, team, taxonomy and every post.',
            self::Publisher => 'Publishes, schedules and features approved posts; manages categories & tags.',
            self::Reviewer => 'Reviews submitted posts: approves them or requests changes.',
            self::Writer => 'Writes posts and submits them for review.',
        };
    }

    /**
     * @return array<int, string>
     */
    public function permissions(): array
    {
        return (array) config('blog.roles.'.$this->value, []);
    }

    public function allows(string $permission): bool
    {
        $granted = $this->permissions();

        if (in_array('*', $granted, true) || in_array($permission, $granted, true)) {
            return true;
        }

        // Support wildcards such as "posts.*"
        foreach ($granted as $g) {
            if (str_ends_with($g, '.*') && str_starts_with($permission, substr($g, 0, -1))) {
                return true;
            }
        }

        return false;
    }

    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $r) => [$r->value => $r->label()])->all();
    }
}
