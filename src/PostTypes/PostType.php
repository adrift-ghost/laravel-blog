<?php

namespace Vitebox\LaravelBlog\PostTypes;

use Vitebox\LaravelBlog\Models\Post;

/**
 * Base class for every post type. Extend it and register the class in
 * config('blog.post_types') to add your own type.
 */
abstract class PostType
{
    public const REQUIRED = 'required';
    public const OPTIONAL = 'optional';
    public const NONE = 'none';

    public function __construct(public readonly string $key)
    {
    }

    abstract public function label(): string;

    public function description(): string
    {
        return '';
    }

    /** A short glyph shown in the admin UI. */
    public function icon(): string
    {
        return '📝';
    }

    /** Cover image: required | optional | none */
    public function coverImage(): string
    {
        return self::OPTIONAL;
    }

    /** Video (URL or upload): required | optional | none */
    public function video(): string
    {
        return self::NONE;
    }

    public function contentRequired(): bool
    {
        return true;
    }

    /** @return array<int, Field> */
    public function fields(): array
    {
        return [];
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        $rules = [];

        foreach ($this->fields() as $field) {
            $rules['fields.'.$field->name] = $field->validationRules();
        }

        return $rules;
    }

    /**
     * Extra, type-specific data exposed in the public API.
     *
     * @return array<string, mixed>
     */
    public function present(Post $post): array
    {
        $out = [];

        foreach ($this->fields() as $field) {
            $value = $post->field($field->name);
            $out[$field->name] = $value instanceof \DateTimeInterface ? $value->format(DATE_ATOM) : $value;
        }

        return $out;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'label' => $this->label(),
            'description' => $this->description(),
            'icon' => $this->icon(),
            'cover_image' => $this->coverImage(),
            'video' => $this->video(),
            'content_required' => $this->contentRequired(),
            'fields' => array_map(fn (Field $f) => [
                'name' => $f->name,
                'label' => $f->label,
                'input' => $f->input,
                'required' => $f->required,
                'options' => $f->options,
            ], $this->fields()),
        ];
    }
}
