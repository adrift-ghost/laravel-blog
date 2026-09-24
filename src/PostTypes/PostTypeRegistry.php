<?php

namespace Vitebox\LaravelBlog\PostTypes;

use InvalidArgumentException;

class PostTypeRegistry
{
    /** @var array<string, PostType> */
    protected array $types = [];

    /** @param array<string, class-string<PostType>> $config */
    public function __construct(array $config = [])
    {
        foreach ($config as $key => $class) {
            $this->register($key, $class);
        }
    }

    /** @param class-string<PostType>|PostType $type */
    public function register(string $key, string|PostType $type): static
    {
        if (is_string($type)) {
            if (! is_subclass_of($type, PostType::class)) {
                throw new InvalidArgumentException("Blog post type [$key] must extend ".PostType::class);
            }
            $type = new $type($key);
        }

        $this->types[$key] = $type;

        return $this;
    }

    public function has(?string $key): bool
    {
        return $key !== null && isset($this->types[$key]);
    }

    public function get(string $key): PostType
    {
        if (! $this->has($key)) {
            throw new InvalidArgumentException("Unknown blog post type [$key].");
        }

        return $this->types[$key];
    }

    /** @return array<string, PostType> */
    public function all(): array
    {
        return $this->types;
    }

    /** @return array<int, string> */
    public function keys(): array
    {
        return array_keys($this->types);
    }

    /** @return array<string, string> */
    public function options(): array
    {
        return array_map(fn (PostType $t) => $t->label(), $this->types);
    }
}
