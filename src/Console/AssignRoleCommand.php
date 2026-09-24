<?php

namespace Vitebox\LaravelBlog\Console;

use Illuminate\Console\Command;
use Vitebox\LaravelBlog\Enums\BlogRole;
use Vitebox\LaravelBlog\Facades\Blog;
use Vitebox\LaravelBlog\Models\TeamMember;

class AssignRoleCommand extends Command
{
    protected $signature = 'blog:role
        {email? : E-mail of the user}
        {role? : admin, publisher, reviewer or writer}
        {--remove : Remove the user from the blog team}
        {--list : List the current blog team}';

    protected $description = 'Assign, change or remove a blog role for a user';

    public function handle(): int
    {
        if ($this->option('list') || ! $this->argument('email')) {
            $rows = TeamMember::query()->with('user')->orderBy('role')->get()->map(fn (TeamMember $m) => [
                Blog::userName($m->user),
                data_get($m->user, config('blog.user_email_column', 'email')),
                $m->role->label(),
                $m->is_active ? 'yes' : 'no',
            ]);
            $this->table(['Name', 'E-mail', 'Role', 'Active'], $rows);

            return self::SUCCESS;
        }

        $user = Blog::findUserByEmail($this->argument('email'));
        if (! $user) {
            $this->components->error('User not found: '.$this->argument('email'));

            return self::FAILURE;
        }

        if ($this->option('remove')) {
            Blog::removeRole($user);
            $this->components->info(Blog::userName($user).' removed from the blog team.');

            return self::SUCCESS;
        }

        $role = BlogRole::tryFrom((string) $this->argument('role'))
            ?? BlogRole::tryFrom((string) $this->choice('Role', array_map(fn ($r) => $r->value, BlogRole::cases()), 'writer'));

        if (! $role) {
            $this->components->error('Unknown role.');

            return self::FAILURE;
        }

        Blog::assignRole($user, $role);
        $this->components->info(Blog::userName($user).' is now a blog '.$role->label().'.');

        return self::SUCCESS;
    }
}
