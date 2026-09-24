<?php

use Vitebox\LaravelBlog\PostTypes;

return [

    /*
    |--------------------------------------------------------------------------
    | Host application user model
    |--------------------------------------------------------------------------
    | The package never modifies your users table. Blog roles are stored in a
    | separate "team" table that references your user's primary key.
    */
    'user_model' => env('BLOG_USER_MODEL', 'App\\Models\\User'),
    'user_name_column' => 'name',
    'user_email_column' => 'email',
    // Type of your users' primary key: 'int', 'uuid', 'ulid' or 'string'.
    // Must be set BEFORE running the migrations.
    'user_key_type' => env('BLOG_USER_KEY_TYPE', 'int'),

    /*
    | Users whose e-mail is listed here are always treated as blog Admins,
    | even before any role has been assigned. Handy for bootstrapping.
    | BLOG_SUPER_ADMINS="owner@example.com,cto@example.com"
    */
    'super_admins' => array_filter(array_map('trim', explode(',', (string) env('BLOG_SUPER_ADMINS', '')))),

    /*
    |--------------------------------------------------------------------------
    | Database
    |--------------------------------------------------------------------------
    */
    'table_prefix' => env('BLOG_TABLE_PREFIX', 'blog_'),
    'database_connection' => env('BLOG_DB_CONNECTION'),

    /*
    |--------------------------------------------------------------------------
    | Admin panel
    |--------------------------------------------------------------------------
    */
    'admin' => [
        'enabled' => true,
        'prefix' => env('BLOG_ADMIN_PREFIX', 'blog-admin'),
        'domain' => env('BLOG_ADMIN_DOMAIN'),
        'middleware' => ['web', 'auth'],
        'route_name' => 'blog.admin.',
        'per_page' => 20,
        'brand' => env('BLOG_ADMIN_BRAND', 'Blog Manager'),
        'login_route' => 'login',
    ],

    /*
    |--------------------------------------------------------------------------
    | Public read-only JSON API (for your website front-end / mobile apps)
    |--------------------------------------------------------------------------
    */
    'api' => [
        'enabled' => true,
        'prefix' => env('BLOG_API_PREFIX', 'api/blog'),
        'middleware' => ['api'],
        'route_name' => 'blog.api.',
        'per_page' => 12,
        'max_per_page' => 50,
        'count_views' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Media uploads
    |--------------------------------------------------------------------------
    */
    'media' => [
        'disk' => env('BLOG_MEDIA_DISK', 'public'),
        'directory' => 'blog',
        'image_mimes' => ['jpg', 'jpeg', 'png', 'webp', 'gif'],
        'max_image_kb' => 5120,
        'video_mimes' => ['mp4', 'webm', 'mov'],
        'max_video_kb' => 204800,
    ],

    /*
    |--------------------------------------------------------------------------
    | Slugs
    |--------------------------------------------------------------------------
    | Slugs are generated from the title when left blank ("auto") or taken from
    | the user's input ("custom"), normalised and made unique. When a slug of a
    | published record changes, the old one is stored and the API answers it
    | with a 301 pointer to the new slug so links never break.
    */
    'slugs' => [
        'separator' => '-',
        'max_length' => 180,
        'language' => 'en',
        'regenerate_on_title_change' => false,
        'keep_redirects' => true,
        'reserved' => ['admin', 'create', 'edit', 'feed', 'rss', 'api', 'search', 'category', 'tag', 'types'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Roles & permissions
    |--------------------------------------------------------------------------
    | Four roles ship by default. You may change which permissions each role
    | holds. "*" grants everything.
    |
    | posts.create        create posts and edit/submit/delete your own drafts
    | posts.view_any      see every post in the admin (not only your own)
    | posts.update_any    edit any post regardless of author/status
    | posts.delete_any    delete any post
    | posts.review        approve / request changes on submitted posts
    | posts.publish       publish, schedule, unpublish, archive, feature, pin
    | categories.manage   create / edit / delete categories
    | tags.create         create new tags while writing a post
    | tags.manage         edit / delete / merge tags
    | team.manage         assign blog roles to users
    */
    'roles' => [
        'admin' => ['*'],
        'publisher' => [
            'posts.create', 'posts.view_any', 'posts.update_any', 'posts.publish',
            'categories.manage', 'tags.create', 'tags.manage',
        ],
        'reviewer' => [
            'posts.create', 'posts.view_any', 'posts.review', 'tags.create',
        ],
        'writer' => [
            'posts.create', 'tags.create',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Editorial workflow
    |--------------------------------------------------------------------------
    | require_review:  submitted posts wait for a Reviewer before a Publisher
    |                  can publish them. When false, "submit" moves straight
    |                  to the "approved" (ready to publish) queue.
    | direct_publish:  users holding posts.publish may publish a draft
    |                  without going through review.
    */
    'workflow' => [
        'require_review' => true,
        'direct_publish' => true,
        'allow_self_review' => false, // may a reviewer approve their own post?
        'schedule_command' => true, // registers blog:publish-scheduled every minute
    ],

    /*
    |--------------------------------------------------------------------------
    | Post types
    |--------------------------------------------------------------------------
    | key => class extending Vitebox\LaravelBlog\PostTypes\PostType.
    | Add your own types here; remove the ones you do not need.
    */
    'post_types' => [
        'standard' => PostTypes\StandardPost::class,
        'video' => PostTypes\VideoPost::class,
        'event' => PostTypes\EventPost::class,
        'article' => PostTypes\ArticlePost::class,
        'news' => PostTypes\NewsPost::class,
        'announcement' => PostTypes\AnnouncementPost::class,
    ],

    'default_post_type' => 'standard',

    /*
    |--------------------------------------------------------------------------
    | Content
    |--------------------------------------------------------------------------
    | sanitizer: class implementing Contracts\HtmlSanitizer, or null to store
    | HTML untouched (only do that if every author is fully trusted).
    | editor:   'textarea' (default, zero dependencies) or 'trix' (loads the
    |           Trix editor from a CDN inside the admin panel).
    */
    'content' => [
        'sanitizer' => \Vitebox\LaravelBlog\Support\BasicHtmlSanitizer::class,
        'allowed_iframe_hosts' => ['www.youtube.com', 'www.youtube-nocookie.com', 'player.vimeo.com'],
        'editor' => env('BLOG_EDITOR', 'textarea'),
        'words_per_minute' => 200,
        'excerpt_length' => 200,
    ],
];
