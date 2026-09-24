<?php

namespace Vitebox\LaravelBlog\Tests\Unit;

use Vitebox\LaravelBlog\Enums\PostStatus;
use Vitebox\LaravelBlog\Models\Category;
use Vitebox\LaravelBlog\Models\Post;
use Vitebox\LaravelBlog\Models\Tag;
use Vitebox\LaravelBlog\Tests\TestCase;

class SlugTest extends TestCase
{
    public function test_slug_is_generated_from_title(): void
    {
        $post = $this->makePost(['title' => 'My First Laravel Post!']);

        $this->assertSame('my-first-laravel-post', $post->slug);
    }

    public function test_slugs_are_unique(): void
    {
        $a = $this->makePost(['title' => 'Same title']);
        $b = $this->makePost(['title' => 'Same title']);
        $c = $this->makePost(['title' => 'Same title']);

        $this->assertSame(['same-title', 'same-title-2', 'same-title-3'], [$a->slug, $b->slug, $c->slug]);
    }

    public function test_custom_slug_is_normalised_and_kept(): void
    {
        $post = $this->makePost(['title' => 'Anything', 'slug' => '  My Custom SLUG__here ']);

        $this->assertSame('my-custom-slug-here', $post->slug);
    }

    public function test_reserved_slugs_are_avoided(): void
    {
        $post = $this->makePost(['title' => 'Admin']);

        $this->assertSame('admin-2', $post->slug);
    }

    public function test_trashed_posts_still_reserve_their_slug(): void
    {
        $this->makePost(['title' => 'Gone'])->delete();

        $this->assertSame('gone-2', $this->makePost(['title' => 'Gone'])->slug);
    }

    public function test_title_change_does_not_change_slug_by_default(): void
    {
        $post = $this->makePost(['title' => 'Original']);
        $post->update(['title' => 'Renamed']);

        $this->assertSame('original', $post->fresh()->slug);
    }

    public function test_changing_slug_of_published_post_keeps_a_redirect(): void
    {
        $post = $this->makePost(['title' => 'Old name'], PostStatus::Published);
        $post->update(['slug' => 'new-name']);

        [$found, $redirected] = Post::findBySlugWithRedirect('old-name');
        $this->assertTrue($found->is($post));
        $this->assertTrue($redirected);

        // A new post can not steal the redirected slug...
        $this->assertSame('old-name-2', $this->makePost(['title' => 'Old name'])->slug);

        // ...but the original post can take it back.
        $post->update(['slug' => 'old-name']);
        $this->assertSame('old-name', $post->fresh()->slug);
        [, $redirected] = Post::findBySlugWithRedirect('old-name');
        $this->assertFalse($redirected);
    }

    public function test_draft_slug_changes_do_not_create_redirects(): void
    {
        $post = $this->makePost(['title' => 'Draft one']);
        $post->update(['slug' => 'draft-two']);

        [$found] = Post::findBySlugWithRedirect('draft-one');
        $this->assertNull($found);
    }

    public function test_categories_and_tags_get_slugs(): void
    {
        $this->assertSame('web-development', Category::query()->create(['name' => 'Web Development'])->slug);
        $this->assertSame('php-8', Tag::query()->create(['name' => 'PHP 8'])->slug);
    }

    public function test_non_latin_title_still_gets_a_slug(): void
    {
        $post = $this->makePost(['title' => '日本語']);

        $this->assertNotEmpty($post->slug);
    }
}
