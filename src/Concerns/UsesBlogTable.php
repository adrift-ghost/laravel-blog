<?php

namespace Vitebox\LaravelBlog\Concerns;

/**
 * Resolves the table name from config('blog.table_prefix') and the optional
 * dedicated connection so the package never collides with host tables.
 */
trait UsesBlogTable
{
    public function getTable(): string
    {
        return config('blog.table_prefix', 'blog_').$this->blogTable;
    }

    public function getConnectionName(): ?string
    {
        return config('blog.database_connection') ?: parent::getConnectionName();
    }
}
