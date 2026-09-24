<?php

namespace Vitebox\LaravelBlog\Http\Controllers\Admin;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Vitebox\LaravelBlog\Http\Controllers\Controller;
use Vitebox\LaravelBlog\Models\Category;
use Vitebox\LaravelBlog\Models\Post;
use Vitebox\LaravelBlog\Models\Tag;
use Vitebox\LaravelBlog\Services\SlugService;

/** Live, unique slug preview used by the admin forms. */
class SlugController extends Controller
{
    public function __invoke(Request $request, SlugService $slugs): JsonResponse
    {
        $data = $request->validate([
            'model' => ['required', 'in:post,category,tag'],
            'value' => ['nullable', 'string', 'max:255'],
            'ignore' => ['nullable', 'integer'],
        ]);

        $model = ['post' => Post::class, 'category' => Category::class, 'tag' => Tag::class][$data['model']];
        $value = trim((string) ($data['value'] ?? ''));

        return response()->json([
            'slug' => $value === '' ? '' : $slugs->preview($model, $value, $data['ignore'] ?? null),
        ]);
    }
}
