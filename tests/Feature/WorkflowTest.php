<?php

namespace Vitebox\LaravelBlog\Tests\Feature;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Vitebox\LaravelBlog\Enums\BlogRole;
use Vitebox\LaravelBlog\Enums\PostStatus;
use Vitebox\LaravelBlog\Events\PostApproved;
use Vitebox\LaravelBlog\Events\PostPublished;
use Vitebox\LaravelBlog\Events\PostSubmittedForReview;
use Vitebox\LaravelBlog\Models\Post;
use Vitebox\LaravelBlog\Tests\TestCase;

class WorkflowTest extends TestCase
{
    public function test_full_editorial_workflow(): void
    {
        Storage::fake('public');
        Event::fake([PostSubmittedForReview::class, PostApproved::class, PostPublished::class]);

        $writer = $this->user(BlogRole::Writer);
        $reviewer = $this->user(BlogRole::Reviewer);
        $publisher = $this->user(BlogRole::Publisher);

        // Writer creates a "cover image + content" post
        $this->actingAs($writer)->post($this->admin('posts.store'), [
            'type' => 'standard',
            'title' => 'Workflow post',
            'content' => '<p>Body</p><script>alert(1)</script>',
            'cover_image' => UploadedFile::fake()->image('cover.jpg', 800, 400),
            'tags' => 'laravel, PHP, laravel',
            'is_featured' => 1, // ignored for writers
        ])->assertRedirect();

        $post = Post::query()->firstOrFail();
        $this->assertSame(PostStatus::Draft, $post->status);
        $this->assertSame('workflow-post', $post->slug);
        $this->assertSame('<p>Body</p>', $post->content);
        $this->assertFalse($post->is_featured);
        $this->assertCount(2, $post->tags);
        Storage::disk('public')->assertExists($post->cover_image);

        // Writer cannot publish
        $this->actingAs($writer)->post(route('blog.admin.posts.publish', $post))->assertForbidden();

        // Writer submits
        $this->actingAs($writer)->post(route('blog.admin.posts.submit', $post))->assertRedirect();
        $this->assertSame(PostStatus::PendingReview, $post->fresh()->status);
        Event::assertDispatched(PostSubmittedForReview::class);

        // ...and can no longer edit it
        $this->actingAs($writer)->put(route('blog.admin.posts.update', $post), ['type' => 'standard', 'title' => 'x', 'content' => 'y'])->assertForbidden();

        // Reviewer requests changes (comment required)
        $this->actingAs($reviewer)->post(route('blog.admin.posts.request-changes', $post))->assertSessionHasErrors('comment');
        $this->actingAs($reviewer)->post(route('blog.admin.posts.request-changes', $post), ['comment' => 'Add a conclusion'])->assertRedirect();
        $this->assertSame(PostStatus::ChangesRequested, $post->fresh()->status);

        // Writer edits and resubmits in one go
        $this->actingAs($writer)->put(route('blog.admin.posts.update', $post), [
            'type' => 'standard', 'title' => 'Workflow post', 'content' => '<p>Body</p><p>Conclusion</p>', 'intent' => 'submit',
        ])->assertRedirect();
        $this->assertSame(PostStatus::PendingReview, $post->fresh()->status);

        // Reviewer approves
        $this->actingAs($reviewer)->post(route('blog.admin.posts.approve', $post))->assertRedirect();
        $post->refresh();
        $this->assertSame(PostStatus::Approved, $post->status);
        $this->assertEquals($reviewer->id, $post->reviewer_id);
        Event::assertDispatched(PostApproved::class);

        // Reviewer cannot publish, publisher can
        $this->actingAs($reviewer)->post(route('blog.admin.posts.publish', $post))->assertForbidden();
        $this->actingAs($publisher)->post(route('blog.admin.posts.publish', $post))->assertRedirect();
        $post->refresh();
        $this->assertSame(PostStatus::Published, $post->status);
        $this->assertTrue($post->isPublished());
        Event::assertDispatched(PostPublished::class);

        $this->assertSame(
            ['created', 'submitted', 'changes_requested', 'updated', 'submitted', 'approved', 'published'],
            $post->activities()->reorder('id')->pluck('action')->all()
        );
    }

    public function test_reviewer_cannot_review_own_post(): void
    {
        $reviewer = $this->user(BlogRole::Reviewer);
        $post = $this->makePost(['author_id' => $reviewer->id], PostStatus::PendingReview);

        $this->actingAs($reviewer)->post(route('blog.admin.posts.approve', $post))->assertForbidden();
    }

    public function test_invalid_transition_is_rejected_gracefully(): void
    {
        $reviewer = $this->user(BlogRole::Reviewer);
        $post = $this->makePost([], PostStatus::Draft);

        $this->actingAs($reviewer)->post(route('blog.admin.posts.approve', $post))->assertForbidden();

        $admin = $this->user(BlogRole::Admin);
        $published = $this->makePost([], PostStatus::Published);
        $this->actingAs($admin)->from('/back')->post(route('blog.admin.posts.submit', $published))->assertForbidden();
        $this->actingAs($admin)->from('/back')->post(route('blog.admin.posts.restore', $published))
            ->assertRedirect('/back')->assertSessionHas('blog_error');
    }

    public function test_scheduling_and_auto_publish(): void
    {
        $publisher = $this->user(BlogRole::Publisher);
        $post = $this->makePost([], PostStatus::Approved);

        $this->actingAs($publisher)->post(route('blog.admin.posts.publish', $post), [
            'publish_at' => now()->addHour()->toDateTimeString(),
        ])->assertRedirect();

        $this->assertSame(PostStatus::Scheduled, $post->fresh()->status);
        $this->assertSame(0, Post::query()->published()->count());

        $this->travel(2)->hours();
        $this->artisan('blog:publish-scheduled')->assertSuccessful();

        $this->assertSame(PostStatus::Published, $post->fresh()->status);
        $this->assertSame(1, Post::query()->published()->count());
    }

    public function test_publisher_can_publish_a_draft_directly_when_enabled(): void
    {
        $publisher = $this->user(BlogRole::Publisher);

        $this->actingAs($publisher)->post($this->admin('posts.store'), [
            'type' => 'article', 'title' => 'Straight out', 'content' => '<p>x</p>', 'intent' => 'publish',
        ])->assertRedirect();

        $this->assertSame(PostStatus::Published, Post::query()->first()->status);

        config(['blog.workflow.direct_publish' => false]);
        $this->actingAs($publisher)->post($this->admin('posts.store'), [
            'type' => 'article', 'title' => 'Needs review', 'content' => '<p>x</p>', 'intent' => 'publish',
        ])->assertSessionHas('blog_error');
        $this->assertSame(PostStatus::Draft, Post::query()->where('title', 'Needs review')->first()->status);
    }

    public function test_access_control_for_admin_panel(): void
    {
        $this->getJson($this->admin('dashboard'))->assertUnauthorized();

        $this->actingAs($this->user())->get($this->admin('dashboard'))->assertForbidden();

        config(['blog.super_admins' => ['boss@example.com']]);
        $boss = $this->user(null, ['email' => 'boss@example.com']);
        $this->actingAs($boss)->get($this->admin('team.index'))->assertOk();
    }

    public function test_writers_only_see_their_own_posts(): void
    {
        $writer = $this->user(BlogRole::Writer);
        $mine = $this->makePost(['title' => 'Mine', 'author_id' => $writer->id]);
        $theirs = $this->makePost(['title' => 'Someone else']);

        $this->actingAs($writer)->get($this->admin('posts.index'))
            ->assertOk()->assertSee('Mine')->assertDontSee('Someone else');

        $this->actingAs($writer)->get(route('blog.admin.posts.show', $theirs))->assertForbidden();
        $this->actingAs($writer)->get(route('blog.admin.posts.show', $mine))->assertOk();
    }

    public function test_writer_can_delete_own_draft_only(): void
    {
        $writer = $this->user(BlogRole::Writer);
        $draft = $this->makePost(['author_id' => $writer->id]);
        $pending = $this->makePost(['author_id' => $writer->id], PostStatus::PendingReview);

        $this->actingAs($writer)->delete(route('blog.admin.posts.destroy', $pending))->assertForbidden();
        $this->actingAs($writer)->delete(route('blog.admin.posts.destroy', $draft))->assertRedirect();
        $this->assertSoftDeleted($draft);
    }
}
