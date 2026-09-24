<?php

namespace Vitebox\LaravelBlog\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Vitebox\LaravelBlog\Http\Controllers\Controller;
use Vitebox\LaravelBlog\Models\Category;

class CategoryController extends Controller
{
    public function index()
    {
        $this->authorize('blog.categories.manage');

        return view('blog::categories.index', [
            'categories' => Category::tree(),
            'category' => new Category(['is_active' => true]),
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('blog.categories.manage');

        Category::query()->create($this->validated($request));

        return redirect($this->route('categories.index'))->with('blog_success', 'Category created.');
    }

    public function edit(Category $category)
    {
        $this->authorize('blog.categories.manage');

        return view('blog::categories.edit', [
            'category' => $category,
            'categories' => Category::tree()->reject(fn ($c) => in_array($c->id, $category->descendantIds(), true)),
        ]);
    }

    public function update(Request $request, Category $category)
    {
        $this->authorize('blog.categories.manage');

        $data = $this->validated($request, $category);

        if ($category->wouldCreateCycle($data['parent_id'] ?? null)) {
            return back()->withInput()->withErrors(['parent_id' => 'A category cannot be placed inside itself or one of its children.']);
        }

        $category->update($data);

        return redirect($this->route('categories.index'))->with('blog_success', 'Category updated.');
    }

    public function destroy(Category $category)
    {
        $this->authorize('blog.categories.manage');

        // Children move up one level instead of being orphaned.
        Category::query()->where('parent_id', $category->id)->update(['parent_id' => $category->parent_id]);
        $category->delete();

        return redirect($this->route('categories.index'))->with('blog_success', 'Category deleted.');
    }

    protected function validated(Request $request, ?Category $category = null): array
    {
        $table = (new Category)->getTable();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'slug' => ['nullable', 'string', 'max:180', 'regex:/^[\pL\pN\s\-_]+$/u'],
            'parent_id' => ['nullable', 'integer', Rule::exists($table, 'id'), Rule::notIn(array_filter([$category?->id]))],
            'description' => ['nullable', 'string', 'max:2000'],
            'color' => ['nullable', 'string', 'max:20', 'regex:/^#?[0-9a-fA-F]{3,8}$/'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:500'],
            'sort_order' => ['nullable', 'integer'],
        ]);

        $data['is_active'] = $request->boolean('is_active');
        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);
        $data['slug'] = $data['slug'] ?? null;

        return $data;
    }
}
