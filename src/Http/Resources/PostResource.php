<?php

namespace Vitebox\LaravelBlog\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Vitebox\LaravelBlog\Facades\Blog;
use Vitebox\LaravelBlog\Models\Post;

/** @mixin Post */
class PostResource extends JsonResource
{
    /** Include the full HTML body (single post endpoint). */
    public bool $withContent = false;

    public function full(): static
    {
        $this->withContent = true;

        return $this;
    }

    public function toArray(Request $request): array
    {
        $type = $this->postType();

        return [
            'id' => $this->id,
            'type' => $this->type,
            'type_label' => $type->label(),
            'title' => $this->title,
            'slug' => $this->slug,
            'excerpt' => $this->summary,
            'content' => $this->when($this->withContent, $this->content),
            'cover_image' => $this->cover_image_url,
            'cover_image_alt' => $this->cover_image_alt ?: $this->title,
            'video' => $this->when($type->video() !== $type::NONE, fn () => $this->video),
            'details' => $type->present($this->resource),
            'author' => $this->whenLoaded('author', fn () => [
                'id' => $this->author?->getKey(),
                'name' => Blog::userName($this->author),
            ]),
            'categories' => CategoryResource::collection($this->whenLoaded('categories')),
            'tags' => TagResource::collection($this->whenLoaded('tags')),
            'is_featured' => $this->is_featured,
            'is_pinned' => $this->is_pinned,
            'reading_time' => $this->reading_time,
            'views' => $this->views,
            'published_at' => $this->published_at?->toAtomString(),
            'updated_at' => $this->updated_at?->toAtomString(),
            'seo' => [
                'title' => $this->meta_title ?: $this->title,
                'description' => $this->meta_description ?: $this->summary,
                'canonical_url' => $this->canonical_url,
                'image' => $this->cover_image_url,
            ],
        ];
    }
}
