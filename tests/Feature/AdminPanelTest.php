<?php

namespace Vitebox\LaravelBlog\Tests\Feature;

use Vitebox\LaravelBlog\Enums\BlogRole;
use Vitebox\LaravelBlog\Enums\PostStatus;
use Vitebox\LaravelBlog\Models\Category;
use Vitebox\LaravelBlog\Models\Post;
use Vitebox\LaravelBlog\Models\Tag;
use Vitebox\LaravelBlog\Models\TeamMember;
use Vitebox\LaravelBlog\Tests\TestCase;

class AdminPanelTest extends TestCase
{
    public function test_every_admin_page_renders_for_an_admin(): void
    {
        $admin = $this->user(BlogRole::Admin);
        $cat = Category::query()->create(['name' => 'News']);
        Category::query()->create(['name' => 'Local', 'parent_id' => $cat->id]);
        $tag = Tag::query()->create(['name' => 'Breaking']);

        $posts = [];
        foreach (['standard', 'video', 'event', 'article', 'news', 'announcement'] as $i => $type) {
            $posts[$type] = $this->makePost([
                'type' => $type,
                'title' => ucfirst($type).' post',
                'video_url' => $type === 'video' ? 'https://youtu.be/dQw4w9WgXcQ' : null,
                'starts_at' => $type === 'event' ? now()->addDay() : null,
                'type_data' => $type === 'announcement' ? ['priority' => 'high', 'cta_url' => 'https://x.test'] : null,
            ], PostStatus::cases()[$i]);
            $posts[$type]->categories()->attach($cat);
            $posts[$type]->tags()->attach($tag);
        }

        $pages = [
            $this->admin('dashboard'), $this->admin('posts.index'), $this->admin('posts.create'),
            $this->admin('posts.create').'?type=event', $this->admin('review.index'),
            $this->admin('categories.index'), route('blog.admin.categories.edit', $cat),
            $this->admin('tags.index'), route('blog.admin.tags.edit', $tag), $this->admin('team.index'),
            $this->admin('posts.index').'?status=draft&type=event&q=post&category='.$cat->id,
        ];
        foreach ($posts as $post) {
            $pages[] = route('blog.admin.posts.show', $post);
            $pages[] = route('blog.admin.posts.edit', $post);
        }

        foreach ($pages as $url) {
            $this->actingAs($admin)->get($url)->assertOk();
        }
    }

    public function test_pages_render_for_each_role(): void
    {
        foreach ([BlogRole::Writer, BlogRole::Reviewer, BlogRole::Publisher] as $role) {
            $user = $this->user($role);
            $post = $this->makePost(['author_id' => $user->id]);
            $this->actingAs($user)->get($this->admin('dashboard'))->assertOk()->assertSee($role->label());
            $this->actingAs($user)->get($this->admin('posts.index'))->assertOk();
            $this->actingAs($user)->get(route('blog.admin.posts.edit', $post))->assertOk();
            $this->actingAs($user)->get(route('blog.admin.posts.show', $post))->assertOk();
        }
    }

    public function test_viewers_of_locked_posts_are_redirected_to_preview(): void
    {
        $writer = $this->user(BlogRole::Writer);
        $post = $this->makePost(['author_id' => $writer->id], PostStatus::PendingReview);

        $this->actingAs($writer)->get(route('blog.admin.posts.edit', $post))
            ->assertRedirect(route('blog.admin.posts.show', $post));
    }

    public function test_category_management_requires_permission(): void
    {
        $writer = $this->user(BlogRole::Writer);
        $publisher = $this->user(BlogRole::Publisher);

        $this->actingAs($writer)->get($this->admin('categories.index'))->assertForbidden();
        $this->actingAs($writer)->post($this->admin('categories.store'), ['name' => 'X'])->assertForbidden();

        $this->actingAs($publisher)->post($this->admin('categories.store'), ['name' => 'Parent', 'is_active' => 1])->assertRedirect();
        $parent = Category::query()->where('slug', 'parent')->firstOrFail();
        $this->actingAs($publisher)->post($this->admin('categories.store'), ['name' => 'Child', 'parent_id' => $parent->id, 'slug' => 'Custom Child'])->assertRedirect();
        $child = Category::query()->where('slug', 'custom-child')->firstOrFail();

        // cannot create a cycle
        $this->actingAs($publisher)->put(route('blog.admin.categories.update', $parent), ['name' => 'Parent', 'parent_id' => $child->id])
            ->assertSessionHasErrors('parent_id');

        // deleting the parent lifts the child up
        $this->actingAs($publisher)->delete(route('blog.admin.categories.destroy', $parent))->assertRedirect();
        $this->assertNull($child->fresh()->parent_id);
    }

    public function test_tags_can_be_merged(): void
    {
        $publisher = $this->user(BlogRole::Publisher);
        $js = Tag::query()->create(['name' => 'js']);
        $javascript = Tag::query()->create(['name' => 'JavaScript']);
        $p1 = $this->makePost(); $p1->tags()->attach($js);
        $p2 = $this->makePost(); $p2->tags()->attach([$js->id, $javascript->id]);

        $this->actingAs($publisher)->post($this->admin('tags.merge'), ['sources' => [$js->id], 'target' => $javascript->id])->assertRedirect();

        $this->assertModelMissing($js);
        $this->assertSame(2, $javascript->posts()->count());
    }

    public function test_writers_without_tag_create_permission_cannot_invent_tags(): void
    {
        config(['blog.roles.writer' => ['posts.create']]);
        Tag::query()->create(['name' => 'Existing']);
        $writer = $this->user(BlogRole::Writer);

        $this->actingAs($writer)->post($this->admin('posts.store'), [
            'type' => 'article', 'title' => 'T', 'content' => 'x', 'tags' => 'existing, brand-new',
        ])->assertSessionHasNoErrors();

        $this->assertSame(['Existing'], Post::query()->first()->tags->pluck('name')->all());
        $this->assertSame(1, Tag::query()->count());
    }

    public function test_team_management(): void
    {
        $admin = $this->user(BlogRole::Admin);
        $publisher = $this->user(BlogRole::Publisher);
        $newbie = $this->user(null, ['email' => 'newbie@example.com']);

        $this->actingAs($publisher)->get($this->admin('team.index'))->assertForbidden();

        $this->actingAs($admin)->post($this->admin('team.store'), ['email' => 'nobody@example.com', 'role' => 'writer'])->assertSessionHasErrors('email');
        $this->actingAs($admin)->post($this->admin('team.store'), ['email' => 'newbie@example.com', 'role' => 'reviewer'])->assertSessionHasNoErrors();
        $this->assertTrue($newbie->fresh()->hasBlogRole('reviewer'));

        // the last admin cannot be demoted or removed
        $adminMember = TeamMember::query()->where('user_id', $admin->id)->first();
        $this->actingAs($admin)->put(route('blog.admin.team.update', $adminMember), ['role' => 'writer', 'is_active' => 1])->assertSessionHas('blog_error');
        $this->actingAs($admin)->delete(route('blog.admin.team.destroy', $adminMember))->assertSessionHas('blog_error');
        $this->assertSame(BlogRole::Admin, $adminMember->fresh()->role);
    }

    public function test_slug_preview_endpoint(): void
    {
        $writer = $this->user(BlogRole::Writer);
        $this->makePost(['title' => 'Taken title']);

        $this->actingAs($writer)->getJson($this->admin('slug').'?model=post&value=Taken Title')
            ->assertOk()->assertJson(['slug' => 'taken-title-2']);
    }

    public function test_artisan_role_command(): void
    {
        $user = $this->user(null, ['email' => 'cli@example.com']);

        $this->artisan('blog:role', ['email' => 'cli@example.com', 'role' => 'publisher'])->assertSuccessful();
        $this->assertTrue($user->hasBlogRole(BlogRole::Publisher));

        $this->artisan('blog:role', ['email' => 'cli@example.com', '--remove' => true])->assertSuccessful();
        $this->assertNull($user->fresh()->blogRole());

        $this->artisan('blog:role', ['email' => 'missing@example.com', 'role' => 'writer'])->assertFailed();
    }
}
