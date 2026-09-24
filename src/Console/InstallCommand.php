<?php

namespace Vitebox\LaravelBlog\Console;

use Illuminate\Console\Command;
use Vitebox\LaravelBlog\Enums\BlogRole;
use Vitebox\LaravelBlog\Facades\Blog;

class InstallCommand extends Command
{
    protected $signature = 'blog:install
        {--admin= : E-mail of an existing user to make blog Admin}
        {--no-migrate : Do not run the migrations}
        {--views : Also publish the Blade views for customisation}
        {--force : Overwrite previously published files}';

    protected $description = 'Install the blog package: publish config, run migrations, link storage and create the first admin';

    public function handle(): int
    {
        $this->components->info('Installing Laravel Blog…');

        $this->callSilently('vendor:publish', ['--tag' => 'blog-config', '--force' => $this->option('force')]);
        $this->components->task('Config published to config/blog.php');

        if ($this->option('views')) {
            $this->callSilently('vendor:publish', ['--tag' => 'blog-views', '--force' => $this->option('force')]);
            $this->components->task('Views published to resources/views/vendor/blog');
        }

        if (! $this->option('no-migrate')) {
            $this->call('migrate', ['--force' => true]);
        }

        if (config('blog.media.disk') === 'public' && ! file_exists(public_path('storage'))) {
            $this->callSilently('storage:link');
            $this->components->task('Storage symlink created (for cover images & videos)');
        }

        $email = $this->option('admin') ?: ($this->input->isInteractive()
            ? $this->ask('E-mail of the user who should become blog Admin (leave empty to skip)')
            : null);

        if ($email) {
            $user = Blog::findUserByEmail($email);
            if ($user) {
                Blog::assignRole($user, BlogRole::Admin);
                $this->components->task("{$email} is now a blog Admin");
            } else {
                $this->components->warn("No user found with e-mail {$email}. Run `php artisan blog:role {$email} admin` once the account exists.");
            }
        }

        $this->newLine();
        $this->components->info('Done! Admin panel: '.url(config('blog.admin.prefix', 'blog-admin')));
        $this->line('  Public API:  '.url(config('blog.api.prefix', 'api/blog').'/posts'));
        $this->line('  Scheduled posts are published by `php artisan schedule:run` (make sure the scheduler cron is set up).');

        return self::SUCCESS;
    }
}
