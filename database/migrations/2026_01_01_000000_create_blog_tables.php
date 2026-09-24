<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function getConnection(): ?string
    {
        return config('blog.database_connection') ?: parent::getConnection();
    }

    private function t(string $name): string
    {
        return config('blog.table_prefix', 'blog_').$name;
    }

    /**
     * Adds a column able to hold the host application's user key (int, uuid or ulid).
     */
    private function userColumn(Blueprint $table, string $column, bool $nullable = false): void
    {
        $type = config('blog.user_key_type', 'int');

        $col = match ($type) {
            'uuid' => $table->uuid($column),
            'ulid' => $table->ulid($column),
            'string' => $table->string($column, 64),
            default => $table->unsignedBigInteger($column),
        };

        if ($nullable) {
            $col->nullable();
        }

        $table->index($column);
    }

    public function up(): void
    {
        $schema = Schema::connection($this->getConnection());

        $schema->create($this->t('team_members'), function (Blueprint $table) {
            $table->id();
            $this->userColumn($table, 'user_id');
            $table->string('role', 20)->index();
            $table->boolean('is_active')->default(true);
            $table->string('display_name')->nullable();
            $table->text('bio')->nullable();
            $table->string('avatar')->nullable();
            $table->timestamps();
            $table->unique('user_id');
        });

        $schema->create($this->t('categories'), function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained($this->t('categories'))->nullOnDelete();
            $table->string('name');
            $table->string('slug', 191)->unique();
            $table->text('description')->nullable();
            $table->string('color', 20)->nullable();
            $table->string('cover_image')->nullable();
            $table->string('meta_title')->nullable();
            $table->string('meta_description', 500)->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        $schema->create($this->t('tags'), function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug', 191)->unique();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        $schema->create($this->t('posts'), function (Blueprint $table) {
            $table->id();
            $table->string('type', 40)->index();
            $table->string('title');
            $table->string('slug', 191)->unique();
            $table->text('excerpt')->nullable();
            $table->longText('content')->nullable();

            $table->string('cover_image')->nullable();
            $table->string('cover_image_alt')->nullable();
            $table->string('video_url')->nullable();
            $table->string('video_path')->nullable();

            $table->string('status', 30)->default('draft')->index();
            $this->userColumn($table, 'author_id');
            $this->userColumn($table, 'reviewer_id', true);
            $this->userColumn($table, 'publisher_id', true);
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('published_at')->nullable()->index();

            // Promoted, queryable type fields (events / announcements)
            $table->timestamp('starts_at')->nullable()->index();
            $table->timestamp('ends_at')->nullable();
            $table->timestamp('expires_at')->nullable()->index();

            $table->boolean('is_featured')->default(false)->index();
            $table->boolean('is_pinned')->default(false)->index();
            $table->unsignedSmallInteger('reading_time')->nullable();
            $table->unsignedBigInteger('views')->default(0);

            $table->string('meta_title')->nullable();
            $table->string('meta_description', 500)->nullable();
            $table->string('canonical_url')->nullable();
            $table->json('type_data')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'published_at']);
        });

        $schema->create($this->t('category_post'), function (Blueprint $table) {
            $table->foreignId('category_id')->constrained($this->t('categories'))->cascadeOnDelete();
            $table->foreignId('post_id')->constrained($this->t('posts'))->cascadeOnDelete();
            $table->primary(['category_id', 'post_id']);
        });

        $schema->create($this->t('post_tag'), function (Blueprint $table) {
            $table->foreignId('post_id')->constrained($this->t('posts'))->cascadeOnDelete();
            $table->foreignId('tag_id')->constrained($this->t('tags'))->cascadeOnDelete();
            $table->primary(['post_id', 'tag_id']);
        });

        $schema->create($this->t('post_activities'), function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained($this->t('posts'))->cascadeOnDelete();
            $this->userColumn($table, 'user_id', true);
            $table->string('action', 40);
            $table->string('from_status', 30)->nullable();
            $table->string('to_status', 30)->nullable();
            $table->text('comment')->nullable();
            $table->timestamp('created_at')->nullable();
        });

        $schema->create($this->t('slug_redirects'), function (Blueprint $table) {
            $table->id();
            $table->string('model_type', 40);
            $table->unsignedBigInteger('model_id');
            $table->string('old_slug', 191);
            $table->timestamps();
            $table->unique(['model_type', 'old_slug']);
            $table->index(['model_type', 'model_id']);
        });
    }

    public function down(): void
    {
        $schema = Schema::connection($this->getConnection());

        foreach (['slug_redirects', 'post_activities', 'post_tag', 'category_post', 'posts', 'tags', 'categories', 'team_members'] as $name) {
            $schema->dropIfExists($this->t($name));
        }
    }
};
