<?php

namespace Vitebox\LaravelBlog\Tests\Feature;

use Vitebox\LaravelBlog\Enums\PostStatus;
use Vitebox\LaravelBlog\Models\Category;
use Vitebox\LaravelBlog\Models\Tag;
use Vitebox\LaravelBlog\Tests\TestCase;

class ApiTest extends TestCase
{
    public function test_only_published_posts_are_listed(): void
    {
        $this->makePost(['title' => 'Live'], PostStatus::Published);
        $this->makePost(['title' => 'Draft']);
        $this->makePost(['title' => 'Future', 'published_at' => now()->addDay()], PostStatus::Scheduled);

        $this->getJson('/api/blog/posts')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Live')
            ->assertJsonMissingPath('data.0.content');
    }

    public function test_filters_by_type_category_and_tag(): void
    {
        $parent = Category::query()->create(['name' => 'Tech']);
        $child = Category::query()->create(['name' => 'Laravel', 'parent_id' => $parent->id]);
        $tag = Tag::query()->create(['name' => 'PHP']);

        $a = $this->makePost(['title' => 'Event A', 'type' => 'event', 'starts_at' => now()->addWeek()], PostStatus::Published);
        $a->categories()->attach($child);
        $b = $this->makePost(['title' => 'News B', 'type' => 'news'], PostStatus::Published);
        $b->tags()->attach($tag);

        $this->getJson('/api/blog/posts?type=event')->assertJsonCount(1, 'data')->assertJsonPath('data.0.title', 'Event A');
        $this->getJson('/api/blog/posts?category=tech')->assertJsonCount(1, 'data'); // includes child categories
        $this->getJson('/api/blog/posts?tag=php')->assertJsonCount(1, 'data')->assertJsonPath('data.0.title', 'News B');
        $this->getJson('/api/blog/events/upcoming')->assertJsonCount(1, 'data')->assertJsonPath('data.0.details.event_state', 'upcoming');
        $this->getJson('/api/blog/categories')->assertJsonPath('data.0.children.0.slug', 'laravel');
        $this->getJson('/api/blog/categories/tech')->assertOk()->assertJsonPath('category.slug', 'tech');
        $this->getJson('/api/blog/tags')->assertJsonPath('data.0.posts_count', 1);
        $this->getJson('/api/blog/types')->assertJsonCount(6, 'data');
    }

    public function test_show_returns_full_post_and_counts_views(): void
    {
        $post = $this->makePost(['title' => 'Readable', 'content' => '<p>Full body</p>'], PostStatus::Published);

        $this->getJson('/api/blog/posts/readable')
            ->assertOk()
            ->assertJsonPath('data.content', '<p>Full body</p>')
            ->assertJsonPath('data.seo.title', 'Readable');

        $this->assertSame(1, $post->fresh()->views);
    }

    public function test_drafts_are_not_reachable_and_old_slugs_redirect(): void
    {
        $this->makePost(['title' => 'Secret']);
        $this->getJson('/api/blog/posts/secret')->assertNotFound();

        $post = $this->makePost(['title' => 'Before'], PostStatus::Published);
        $post->update(['slug' => 'after']);

        $this->getJson('/api/blog/posts/before')
            ->assertStatus(301)
            ->assertJsonPath('slug', 'after')
            ->assertHeader('Location', route('blog.api.posts.show', 'after'));
    }

    public function test_pinned_posts_come_first(): void
    {
        $this->makePost(['title' => 'Older pinned', 'is_pinned' => true, 'published_at' => now()->subDays(5)], PostStatus::Published);
        $this->makePost(['title' => 'Newest'], PostStatus::Published);

        $this->getJson('/api/blog/posts')->assertJsonPath('data.0.title', 'Older pinned');
    }
}
