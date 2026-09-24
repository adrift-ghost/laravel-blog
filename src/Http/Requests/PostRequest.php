<?php

namespace Vitebox\LaravelBlog\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Vitebox\LaravelBlog\Facades\Blog;
use Vitebox\LaravelBlog\Models\Category;
use Vitebox\LaravelBlog\Models\Post;
use Vitebox\LaravelBlog\PostTypes\PostType;

class PostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // controllers authorise through PostPolicy
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'slug' => $this->filled('slug') ? trim((string) $this->input('slug')) : null,
            'is_featured' => $this->boolean('is_featured'),
            'is_pinned' => $this->boolean('is_pinned'),
            'remove_cover_image' => $this->boolean('remove_cover_image'),
            'remove_video' => $this->boolean('remove_video'),
        ]);
    }

    public function rules(): array
    {
        $types = Blog::types();
        $typeKey = (string) $this->input('type', config('blog.default_post_type'));
        $type = $types->has($typeKey) ? $types->get($typeKey) : null;

        /** @var Post|null $post */
        $post = $this->route('post');
        $media = config('blog.media');
        $imageRule = ['image', 'mimes:'.implode(',', $media['image_mimes']), 'max:'.$media['max_image_kb']];

        $rules = [
            'type' => ['required', Rule::in($types->keys())],
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:'.config('blog.slugs.max_length', 180), 'regex:/^[\pL\pN\s\-_]+$/u'],
            'excerpt' => ['nullable', 'string', 'max:1000'],
            'content' => [$type?->contentRequired() ? 'required' : 'nullable', 'string'],
            'cover_image' => array_merge(['nullable'], $imageRule),
            'cover_image_alt' => ['nullable', 'string', 'max:255'],
            'remove_cover_image' => ['boolean'],
            'video_url' => ['nullable', 'url', 'max:2048'],
            'video_file' => ['nullable', 'file', 'mimes:'.implode(',', $media['video_mimes']), 'max:'.$media['max_video_kb']],
            'remove_video' => ['boolean'],
            'categories' => ['nullable', 'array'],
            'categories.*' => ['nullable', 'integer', Rule::exists((new Category)->getTable(), 'id')],
            'tags' => ['nullable'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:500'],
            'canonical_url' => ['nullable', 'url', 'max:2048'],
            'is_featured' => ['boolean'],
            'is_pinned' => ['boolean'],
            'fields' => ['nullable', 'array'],
            'revision_note' => ['nullable', 'string', 'max:500'],
            'intent' => ['nullable', Rule::in(['save', 'submit', 'publish'])],
            'publish_at' => ['nullable', 'date'],
        ];

        if ($type) {
            $rules = array_merge($rules, $type->rules());

            $hasCover = $post?->cover_image && ! $this->boolean('remove_cover_image');
            if ($type->coverImage() === PostType::REQUIRED && ! $hasCover) {
                $rules['cover_image'] = array_merge(['required'], $imageRule);
            }

            $hasVideo = ($post?->video_path && ! $this->boolean('remove_video'));
            if ($type->video() === PostType::REQUIRED && ! $hasVideo) {
                $rules['video_url'] = ['required_without:video_file', 'nullable', 'url', 'max:2048'];
                $rules['video_file'][0] = 'required_without:video_url';
            }
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'cover_image.required' => 'This post type needs a cover image.',
            'video_url.required_without' => 'Add a video URL (YouTube / Vimeo / .mp4) or upload a video file.',
            'video_file.required_without' => 'Add a video URL (YouTube / Vimeo / .mp4) or upload a video file.',
            'slug.regex' => 'The slug may only contain letters, numbers, spaces, dashes and underscores.',
        ];
    }

    public function attributes(): array
    {
        $attrs = [];
        $typeKey = (string) $this->input('type');
        if (Blog::types()->has($typeKey)) {
            foreach (Blog::types()->get($typeKey)->fields() as $field) {
                $attrs['fields.'.$field->name] = strtolower($field->label);
            }
        }

        return $attrs;
    }
}
