<?php

namespace Vitebox\LaravelBlog;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Vitebox\LaravelBlog\Console\AssignRoleCommand;
use Vitebox\LaravelBlog\Console\InstallCommand;
use Vitebox\LaravelBlog\Console\PublishScheduledCommand;
use Vitebox\LaravelBlog\Contracts\HtmlSanitizer;
use Vitebox\LaravelBlog\Facades\Blog;
use Vitebox\LaravelBlog\Http\Middleware\EnsureBlogAccess;
use Vitebox\LaravelBlog\Models\Post;
use Vitebox\LaravelBlog\Policies\PostPolicy;
use Vitebox\LaravelBlog\PostTypes\PostTypeRegistry;
use Vitebox\LaravelBlog\Services\PostService;
use Vitebox\LaravelBlog\Services\PostWorkflow;
use Vitebox\LaravelBlog\Services\SlugService;

class BlogServiceProvider extends ServiceProvider
{
    /** Every permission understood by the package (see config/blog.php). */
    public const PERMISSIONS = [
        'posts.create', 'posts.view_any', 'posts.update_any', 'posts.delete_any',
        'posts.review', 'posts.publish', 'categories.manage', 'tags.create',
        'tags.manage', 'team.manage',
    ];

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/blog.php', 'blog');

        $this->app->singleton(PostTypeRegistry::class, fn () => new PostTypeRegistry((array) config('blog.post_types', [])));
        $this->app->singleton(BlogManager::class);
        $this->app->singleton(SlugService::class);
        $this->app->singleton(PostWorkflow::class);

        if ($sanitizer = config('blog.content.sanitizer')) {
            $this->app->singleton(HtmlSanitizer::class, $sanitizer);
        }

        $this->app->singleton(PostService::class, fn ($app) => new PostService(
            $app->make(PostWorkflow::class),
            $app->bound(HtmlSanitizer::class) ? $app->make(HtmlSanitizer::class) : null,
        ));
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'blog');
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        $this->registerAuthorization();
        $this->registerRoutes();
        $this->registerViewComposers();

        if ($this->app->runningInConsole()) {
            $this->registerPublishing();

            $this->commands([
                InstallCommand::class,
                AssignRoleCommand::class,
                PublishScheduledCommand::class,
            ]);
        }

        if (config('blog.workflow.schedule_command', true)) {
            $this->callAfterResolving(Schedule::class, function (Schedule $schedule) {
                $schedule->command('blog:publish-scheduled')->everyMinute()->withoutOverlapping();
            });
        }
    }

    protected function registerAuthorization(): void
    {
        Gate::policy(Post::class, PostPolicy::class);

        Gate::define('blog.access', fn ($user) => Blog::hasAccess($user));

        foreach (self::PERMISSIONS as $permission) {
            Gate::define('blog.'.$permission, fn ($user) => Blog::can($user, $permission));
        }
    }

    protected function registerRoutes(): void
    {
        if ($this->app->routesAreCached()) {
            return;
        }

        $this->app['router']->aliasMiddleware('blog.access', EnsureBlogAccess::class);

        if (config('blog.admin.enabled', true)) {
            Route::group(array_filter([
                'prefix' => config('blog.admin.prefix', 'blog-admin'),
                'domain' => config('blog.admin.domain'),
                'middleware' => array_merge((array) config('blog.admin.middleware', ['web', 'auth']), ['blog.access']),
                'as' => config('blog.admin.route_name', 'blog.admin.'),
            ]), fn () => $this->loadRoutesFrom(__DIR__.'/../routes/admin.php'));
        }

        if (config('blog.api.enabled', true)) {
            Route::group([
                'prefix' => config('blog.api.prefix', 'api/blog'),
                'middleware' => (array) config('blog.api.middleware', ['api']),
                'as' => config('blog.api.route_name', 'blog.api.'),
            ], fn () => $this->loadRoutesFrom(__DIR__.'/../routes/api.php'));
        }
    }

    protected function registerViewComposers(): void
    {
        View::composer('blog::*', function ($view) {
            $user = auth()->user();
            $view->with('blog', $this->app->make(BlogManager::class));
            $view->with('blogUser', $user);
            $view->with('blogRole', Blog::roleOf($user));
        });
    }

    protected function registerPublishing(): void
    {
        $this->publishes([
            __DIR__.'/../config/blog.php' => config_path('blog.php'),
        ], 'blog-config');

        $this->publishes([
            __DIR__.'/../database/migrations' => database_path('migrations'),
        ], 'blog-migrations');

        $this->publishes([
            __DIR__.'/../resources/views' => resource_path('views/vendor/blog'),
        ], 'blog-views');
    }
}
