<?php

namespace Vitebox\LaravelBlog\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Vitebox\LaravelBlog\Contracts\HtmlSanitizer;
use Vitebox\LaravelBlog\Facades\Blog;
use Vitebox\LaravelBlog\Models\Post;
use Vitebox\LaravelBlog\Models\Tag;

/**
 * Creates / updates posts from validated input: content sanitising,
 * type-specific fields, media uploads, categories and tags.
 */
class PostService
{
    public function __construct(
        protected PostWorkflow $workflow,
        protected ?HtmlSanitizer $sanitizer = null,
    ) {
    }

    /**
     * @param  array<string, mixed>  $data  validated input
     */
    public function save(Post $post, array $data, mixed $user): Post
    {
        $isNew = ! $post->exists;
        $type = Blog::types()->get($data['type'] ?? $post->type ?? config('blog.default_post_type'));

        return DB::connection($post->getConnectionName())->transaction(function () use ($post, $data, $user, $isNew, $type) {
            $post->fill(Arr::only($data, [
                'title', 'slug', 'excerpt', 'cover_image_alt', 'video_url',
                'meta_title', 'meta_description', 'canonical_url',
            ]));
            $post->type = $type->key;

            if (array_key_exists('content', $data)) {
                $post->content = $this->sanitize((string) $data['content']);
            }

            // Publisher-only flags (controller strips them for other roles)
            foreach (['is_featured', 'is_pinned'] as $flag) {
                if (array_key_exists($flag, $data)) {
                    $post->{$flag} = (bool) $data[$flag];
                }
            }

            $this->applyTypeFields($post, $type, (array) ($data['fields'] ?? []));
            $this->handleMedia($post, $data, $type);

            if ($isNew) {
                $post->author_id = $data['author_id'] ?? $user?->getAuthIdentifier();
            }

            $post->save();

            if (array_key_exists('categories', $data)) {
                $post->categories()->sync(array_filter((array) $data['categories']));
            }

            if (array_key_exists('tags', $data)) {
                $post->tags()->sync(Tag::resolveIds($data['tags'] ?? [], (bool) ($data['_can_create_tags'] ?? true))->all());
            }

            $this->workflow->log($post, $user, $isNew ? 'created' : 'updated', null, null, $data['revision_note'] ?? null);

            return $post->refresh();
        });
    }

    public function delete(Post $post, bool $force = false): void
    {
        if ($force) {
            $this->deleteFile($post->cover_image);
            $this->deleteFile($post->video_path);
            $post->forceDelete();

            return;
        }

        $post->delete();
    }

    public function sanitize(string $html): string
    {
        return $this->sanitizer ? $this->sanitizer->sanitize($html) : $html;
    }

    protected function applyTypeFields(Post $post, $type, array $input): void
    {
        $json = [];
        $columns = ['starts_at' => null, 'ends_at' => null, 'expires_at' => null];

        foreach ($type->fields() as $field) {
            $value = $field->cast($input[$field->name] ?? $field->default);

            if ($field->column) {
                $columns[$field->column] = $value;
            } else {
                $json[$field->name] = $value;
            }
        }

        // Columns not used by the (possibly new) type are cleared.
        foreach ($columns as $column => $value) {
            $post->setAttribute($column, $value);
        }

        $post->type_data = array_filter($json, fn ($v) => $v !== null) ?: null;
    }

    protected function handleMedia(Post $post, array $data, $type): void
    {
        if (! empty($data['remove_cover_image'])) {
            $this->deleteFile($post->cover_image);
            $post->cover_image = null;
        }

        if (($data['cover_image'] ?? null) instanceof UploadedFile) {
            $this->deleteFile($post->cover_image);
            $post->cover_image = $this->store($data['cover_image'], 'covers');
        }

        if ($type->video() === $type::NONE) {
            $this->deleteFile($post->video_path);
            $post->video_path = null;
            $post->video_url = null;

            return;
        }

        if (! empty($data['remove_video'])) {
            $this->deleteFile($post->video_path);
            $post->video_path = null;
        }

        if (($data['video_file'] ?? null) instanceof UploadedFile) {
            $this->deleteFile($post->video_path);
            $post->video_path = $this->store($data['video_file'], 'videos');
        }
    }

    protected function store(UploadedFile $file, string $folder): string
    {
        $dir = trim(config('blog.media.directory', 'blog'), '/').'/'.$folder.'/'.now()->format('Y/m');

        return $file->store($dir, config('blog.media.disk', 'public'));
    }

    protected function deleteFile(?string $path): void
    {
        if ($path && ! preg_match('~^https?://~i', $path)) {
            Storage::disk(config('blog.media.disk', 'public'))->delete($path);
        }
    }
}
