<?php

namespace Vitebox\LaravelBlog\Tests;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Orchestra\Testbench\TestCase as Orchestra;
use Vitebox\LaravelBlog\BlogServiceProvider;
use Vitebox\LaravelBlog\Enums\BlogRole;
use Vitebox\LaravelBlog\Enums\PostStatus;
use Vitebox\LaravelBlog\Facades\Blog;
use Vitebox\LaravelBlog\Models\Post;
use Vitebox\LaravelBlog\Tests\Fixtures\User;

abstract class TestCase extends Orchestra
{
    use RefreshDatabase;

    protected function getPackageProviders($app): array
    {
        return [BlogServiceProvider::class];
    }

    protected function getPackageAliases($app): array
    {
        return ['Blog' => Blog::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.key', 'base64:'.base64_encode(str_repeat('a', 32)));
        $app['config']->set('database.default', 'testing');
        $app['config']->set('blog.user_model', User::class);
        $app['config']->set('auth.providers.users.model', User::class);
        $app['config']->set('blog.super_admins', []);
    }

    protected function defineDatabaseMigrations(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });
    }

    protected function user(BlogRole|string|null $role = null, array $attributes = []): User
    {
        $user = User::query()->create(array_merge([
            'name' => 'User '.Str::random(5),
            'email' => Str::lower(Str::random(8)).'@example.com',
            'password' => 'secret',
        ], $attributes));

        if ($role) {
            Blog::assignRole($user, $role);
        }

        return $user;
    }

    protected function makePost(array $attributes = [], PostStatus $status = PostStatus::Draft): Post
    {
        $post = new Post(array_merge([
            'type' => 'article',
            'title' => 'Hello World',
            'content' => '<p>Some content for the post body.</p>',
        ], $attributes));
        $post->author_id ??= $this->user(BlogRole::Writer)->id;
        $post->status = $status;
        if ($status === PostStatus::Published && ! $post->published_at) {
            $post->published_at = now()->subMinute();
        }
        $post->save();

        return $post;
    }

    protected function admin(string $name): string
    {
        return route('blog.admin.'.$name, [], false);
    }
}
