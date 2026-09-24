<?php

namespace Vitebox\LaravelBlog\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Vitebox\LaravelBlog\Models\SlugRedirect;

class SlugService
{
    /**
     * Decide whether the model needs a (new) slug and assign a unique one.
     */
    public function apply(Model $model): void
    {
        $sourceColumn = $model->slugSourceColumn();
        $source = (string) $model->getAttribute($sourceColumn);
        $current = trim((string) $model->getAttribute('slug'));

        if ($current === '') {
            $base = $source;                                    // auto
        } elseif (! $model->exists || $model->isDirty('slug')) {
            $base = $current;                                   // custom
        } elseif ($model->isDirty($sourceColumn)
            && config('blog.slugs.regenerate_on_title_change', false)
            && (! method_exists($model, 'isPublished') || ! $model->isPublished())) {
            $base = $source;                                    // regenerate
        } else {
            return;                                             // unchanged
        }

        $model->setAttribute('slug', $this->unique($model, $this->normalize($base), $model->getKey()));
    }

    public function normalize(string $value): string
    {
        $sep = config('blog.slugs.separator', '-');
        $slug = Str::slug($value, $sep, config('blog.slugs.language', 'en'));

        if ($slug === '') {
            // e.g. titles written entirely in a non-transliterable script
            $slug = 'post'.$sep.substr(md5($value.microtime()), 0, 8);
        }

        $max = (int) config('blog.slugs.max_length', 180);
        if (strlen($slug) > $max) {
            $slug = rtrim(substr($slug, 0, $max), $sep);
        }

        return $slug;
    }

    /**
     * @param  Model|class-string<Model>  $model
     */
    public function unique(Model|string $model, string $slug, mixed $ignoreId = null): string
    {
        $model = is_string($model) ? new $model : $model;
        $sep = config('blog.slugs.separator', '-');
        $reserved = (array) config('blog.slugs.reserved', []);

        $candidate = $slug;
        $i = 1;

        if (in_array($candidate, $reserved, true)) {
            $candidate = $slug.$sep.(++$i);
        }

        while ($this->taken($model, $candidate, $ignoreId)) {
            $candidate = $slug.$sep.(++$i);
        }

        return $candidate;
    }

    /** Live preview for the admin form (no save). */
    public function preview(Model|string $model, string $value, mixed $ignoreId = null): string
    {
        return $this->unique($model, $this->normalize($value), $ignoreId);
    }

    protected function taken(Model $model, string $slug, mixed $ignoreId): bool
    {
        $query = $model->newQueryWithoutScopes()->where('slug', $slug);

        if ($ignoreId !== null) {
            $query->where($model->getKeyName(), '!=', $ignoreId);
        }

        if ($query->exists()) {
            return true;
        }

        // An old slug that still redirects to *another* record is also taken.
        return SlugRedirect::query()
            ->where('model_type', $model::slugRedirectKey())
            ->where('old_slug', $slug)
            ->when($ignoreId !== null, fn ($q) => $q->where('model_id', '!=', $ignoreId))
            ->exists();
    }
}
