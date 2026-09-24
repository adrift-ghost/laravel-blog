<?php

namespace Vitebox\LaravelBlog\Concerns;

use Illuminate\Database\Eloquent\Model;
use Vitebox\LaravelBlog\Models\SlugRedirect;
use Vitebox\LaravelBlog\Services\SlugService;

/**
 * Auto + custom slug management.
 *
 *  - blank slug          -> generated from the source column (title / name)
 *  - user-supplied slug  -> normalised (lower-case, transliterated, hyphenated)
 *  - always unique       -> "-2", "-3"... appended on collision (incl. trashed)
 *  - slug changed        -> old slug kept in slug_redirects for 301s
 *
 * @mixin Model
 */
trait HasSlug
{
    public static function bootHasSlug(): void
    {
        static::saving(function (Model $model) {
            app(SlugService::class)->apply($model);
        });

        static::updated(function (Model $model) {
            if (! $model->wasChanged('slug') || ! config('blog.slugs.keep_redirects', true)) {
                return;
            }

            $old = $model->getOriginal('slug');
            if ($old && $model->shouldKeepSlugRedirect()) {
                SlugRedirect::remember(static::slugRedirectKey(), $model->getKey(), $old);
            }

            // The new slug can no longer be a redirect for this model type.
            SlugRedirect::query()
                ->where('model_type', static::slugRedirectKey())
                ->where('old_slug', $model->slug)
                ->delete();
        });

        static::deleted(function (Model $model) {
            if (method_exists($model, 'isForceDeleting') && ! $model->isForceDeleting()) {
                return;
            }
            SlugRedirect::query()
                ->where('model_type', static::slugRedirectKey())
                ->where('model_id', $model->getKey())
                ->delete();
        });
    }

    /** Column the automatic slug is generated from. */
    public function slugSourceColumn(): string
    {
        return 'name';
    }

    /** Short key identifying this model in slug_redirects. */
    public static function slugRedirectKey(): string
    {
        return strtolower(class_basename(static::class));
    }

    /** Whether an old slug should keep redirecting (e.g. only once public). */
    public function shouldKeepSlugRedirect(): bool
    {
        return true;
    }

    /**
     * Find a record by current slug, falling back to historical slugs.
     *
     * @return array{0: static|null, 1: bool} [model, wasRedirected]
     */
    public static function findBySlugWithRedirect(string $slug, ?\Closure $scope = null): array
    {
        $query = static::query();
        if ($scope) {
            $scope($query);
        }

        if ($model = (clone $query)->where('slug', $slug)->first()) {
            return [$model, false];
        }

        $redirect = SlugRedirect::query()
            ->where('model_type', static::slugRedirectKey())
            ->where('old_slug', $slug)
            ->first();

        if ($redirect && ($model = $query->whereKey($redirect->model_id)->first())) {
            return [$model, true];
        }

        return [null, false];
    }
}
