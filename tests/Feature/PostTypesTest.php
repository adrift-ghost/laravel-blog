<?php

namespace Vitebox\LaravelBlog\Tests\Feature;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Vitebox\LaravelBlog\Enums\BlogRole;
use Vitebox\LaravelBlog\Enums\PostStatus;
use Vitebox\LaravelBlog\Facades\Blog;
use Vitebox\LaravelBlog\Models\Post;
use Vitebox\LaravelBlog\PostTypes\Field;
use Vitebox\LaravelBlog\PostTypes\PostType;
use Vitebox\LaravelBlog\Tests\TestCase;

class PostTypesTest extends TestCase
{
    public function test_six_post_types_ship_by_default(): void
    {
        $this->assertSame(['standard', 'video', 'event', 'article', 'news', 'announcement'], Blog::types()->keys());
    }

    public function test_standard_post_requires_cover_image(): void
    {
        $this->actingAs($this->user(BlogRole::Writer))
            ->post($this->admin('posts.store'), ['type' => 'standard', 'title' => 'No cover', 'content' => 'x'])
            ->assertSessionHasErrors('cover_image');
    }

    public function test_video_post_requires_cover_and_a_video(): void
    {
        Storage::fake('public');
        $writer = $this->user(BlogRole::Writer);

        $this->actingAs($writer)->post($this->admin('posts.store'), [
            'type' => 'video', 'title' => 'Video', 'content' => 'x',
            'cover_image' => UploadedFile::fake()->image('c.png'),
        ])->assertSessionHasErrors(['video_url', 'video_file']);

        $this->actingAs($writer)->post($this->admin('posts.store'), [
            'type' => 'video', 'title' => 'Video', 'content' => 'x',
            'cover_image' => UploadedFile::fake()->image('c.png'),
            'video_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            'fields' => ['duration' => '3:32'],
        ])->assertSessionHasNoErrors();

        $post = Post::query()->firstOrFail();
        $this->assertSame('youtube', $post->video['provider']);
        $this->assertSame('3:32', $post->field('duration'));
    }

    public function test_event_fields_are_validated_and_stored_in_columns(): void
    {
        $writer = $this->user(BlogRole::Writer);

        $this->actingAs($writer)->post($this->admin('posts.store'), [
            'type' => 'event', 'title' => 'Meetup', 'content' => 'x',
            'fields' => ['starts_at' => '2030-05-10 18:00', 'ends_at' => '2030-05-09 10:00'],
        ])->assertSessionHasErrors('fields.ends_at');

        $this->actingAs($writer)->post($this->admin('posts.store'), [
            'type' => 'event', 'title' => 'Meetup', 'content' => 'x',
            'fields' => [
                'starts_at' => '2030-05-10 18:00', 'ends_at' => '2030-05-10 21:00',
                'venue' => 'Hall A', 'is_online' => '0', 'registration_url' => 'https://tickets.test',
            ],
        ])->assertSessionHasNoErrors();

        $post = Post::query()->firstOrFail();
        $this->assertSame('2030-05-10 18:00', $post->starts_at->format('Y-m-d H:i'));
        $this->assertSame('Hall A', $post->field('venue'));
        $this->assertFalse($post->field('is_online'));
        $this->assertSame(1, Post::query()->upcomingEvents()->count());
    }

    public function test_changing_type_clears_fields_of_the_old_type(): void
    {
        $admin = $this->user(BlogRole::Admin);
        $post = $this->makePost(['type' => 'event', 'starts_at' => now()->addDay(), 'type_data' => ['venue' => 'X']]);

        $this->actingAs($admin)->put(route('blog.admin.posts.update', $post), [
            'type' => 'news', 'title' => 'Now news', 'content' => 'x', 'fields' => ['is_breaking' => '1', 'source_name' => 'Wire'],
        ])->assertSessionHasNoErrors();

        $post->refresh();
        $this->assertNull($post->starts_at);
        $this->assertSame(['is_breaking' => true, 'source_name' => 'Wire'], $post->type_data);
    }

    public function test_announcement_priority_and_expiry(): void
    {
        $writer = $this->user(BlogRole::Writer);

        $this->actingAs($writer)->post($this->admin('posts.store'), [
            'type' => 'announcement', 'title' => 'Office closed', 'content' => 'x',
            'fields' => ['priority' => 'urgent'],
        ])->assertSessionHasErrors('fields.priority');

        $this->makePost(['type' => 'announcement', 'title' => 'Old', 'expires_at' => now()->subDay()], PostStatus::Published);
        $this->makePost(['type' => 'announcement', 'title' => 'Current', 'expires_at' => now()->addDay()], PostStatus::Published);
        $this->makePost(['type' => 'announcement', 'title' => 'Forever'], PostStatus::Published);

        $this->assertEqualsCanonicalizing(['Current', 'Forever'], Post::query()->published()->activeAnnouncements()->pluck('title')->all());
    }

    public function test_custom_post_types_can_be_registered(): void
    {
        Blog::types()->register('podcast', new class('podcast') extends PostType {
            public function label(): string { return 'Podcast'; }
            public function fields(): array { return [Field::url('audio_url', 'Audio URL')->required()]; }
        });

        $writer = $this->user(BlogRole::Writer);
        $this->actingAs($writer)->post($this->admin('posts.store'), ['type' => 'podcast', 'title' => 'Ep 1', 'content' => 'x'])
            ->assertSessionHasErrors('fields.audio_url');
        $this->actingAs($writer)->post($this->admin('posts.store'), ['type' => 'podcast', 'title' => 'Ep 1', 'content' => 'x', 'fields' => ['audio_url' => 'https://a.test/1.mp3']])
            ->assertSessionHasNoErrors();

        $this->assertSame('https://a.test/1.mp3', Post::query()->first()->field('audio_url'));
    }
}
