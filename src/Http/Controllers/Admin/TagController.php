<?php

namespace Vitebox\LaravelBlog\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Vitebox\LaravelBlog\Facades\Blog;
use Vitebox\LaravelBlog\Http\Controllers\Controller;
use Vitebox\LaravelBlog\Models\Tag;

class TagController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('blog.tags.manage');

        return view('blog::tags.index', [
            'tags' => Tag::query()
                ->withCount('posts')
                ->when($request->filled('q'), fn ($q) => $q->where('name', 'like', '%'.$request->input('q').'%'))
                ->orderBy('name')
                ->paginate(50)
                ->withQueryString(),
            'allTags' => Tag::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('blog.tags.manage');

        Tag::query()->create($this->validated($request));

        return back()->with('blog_success', 'Tag created.');
    }

    public function edit(Tag $tag)
    {
        $this->authorize('blog.tags.manage');

        return view('blog::tags.edit', ['tag' => $tag]);
    }

    public function update(Request $request, Tag $tag)
    {
        $this->authorize('blog.tags.manage');

        $tag->update($this->validated($request, $tag));

        return redirect($this->route('tags.index'))->with('blog_success', 'Tag updated.');
    }

    public function destroy(Tag $tag)
    {
        $this->authorize('blog.tags.manage');

        $tag->delete();

        return back()->with('blog_success', 'Tag deleted.');
    }

    /** Merge several tags into one (their posts are re-tagged). */
    public function merge(Request $request)
    {
        $this->authorize('blog.tags.manage');

        $table = (new Tag)->getTable();
        $data = $request->validate([
            'sources' => ['required', 'array', 'min:1'],
            'sources.*' => ['integer', Rule::exists($table, 'id')],
            'target' => ['required', 'integer', Rule::exists($table, 'id')],
        ]);

        $sources = array_values(array_diff($data['sources'], [$data['target']]));
        $pivot = Blog::table('post_tag');
        $conn = (new Tag)->getConnection();

        $conn->transaction(function () use ($conn, $pivot, $sources, $data) {
            $postIds = $conn->table($pivot)->whereIn('tag_id', $sources)->pluck('post_id')->unique();
            $already = $conn->table($pivot)->where('tag_id', $data['target'])->pluck('post_id');

            $conn->table($pivot)->insert(
                $postIds->diff($already)->map(fn ($id) => ['post_id' => $id, 'tag_id' => $data['target']])->values()->all()
            );

            Tag::query()->whereIn('id', $sources)->get()->each->delete();
        });

        return back()->with('blog_success', count($sources).' tag(s) merged.');
    }

    protected function validated(Request $request, ?Tag $tag = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:60', Rule::unique((new Tag)->getTable(), 'name')->ignore($tag?->id)],
            'slug' => ['nullable', 'string', 'max:180', 'regex:/^[\pL\pN\s\-_]+$/u'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);
        $data['slug'] = $data['slug'] ?? null;

        return $data;
    }
}
