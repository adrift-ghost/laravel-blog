<?php

namespace Vitebox\LaravelBlog\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \Vitebox\LaravelBlog\Models\Category */
class CategoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'color' => $this->color,
            'parent_id' => $this->parent_id,
            'posts_count' => $this->whenCounted('posts'),
            'children' => static::collection($this->whenLoaded('children')),
            'seo' => [
                'title' => $this->meta_title ?: $this->name,
                'description' => $this->meta_description ?: $this->description,
            ],
        ];
    }
}
