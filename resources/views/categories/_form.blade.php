<div class="field">
    <label for="cat-name">Name</label>
    <input type="text" id="cat-name" name="name" value="{{ old('name', $category->name) }}" required maxlength="120">
</div>
<div class="field">
    <div class="row" style="justify-content:space-between">
        <label for="cat-slug" style="margin:0">Slug</label>
        <label class="check small" style="margin:0"><input type="checkbox" id="cat-slug-auto" @checked(!$category->exists && !old('slug'))> Auto</label>
    </div>
    <input type="text" id="cat-slug" name="slug" value="{{ old('slug', $category->slug) }}" style="margin-top:6px"
           data-slug-model="category" data-slug-source="#cat-name" data-slug-auto="#cat-slug-auto" data-slug-hint="#cat-slug-hint"
           @if($category->exists) data-slug-ignore="{{ $category->id }}" @endif>
    <div class="help" id="cat-slug-hint"></div>
</div>
<div class="field">
    <label for="cat-parent">Parent</label>
    <select id="cat-parent" name="parent_id">
        <option value="">— None (top level) —</option>
        @foreach($categories as $option)
            @continue($category->exists && $option->id === $category->id)
            <option value="{{ $option->id }}" @selected((string) old('parent_id', $category->parent_id) === (string) $option->id)>{{ str_repeat('— ', $option->depth) }}{{ $option->name }}</option>
        @endforeach
    </select>
</div>
<div class="field">
    <label for="cat-description">Description</label>
    <textarea id="cat-description" name="description" rows="3">{{ old('description', $category->description) }}</textarea>
</div>
<div class="grid grid-2">
    <div class="field">
        <label for="cat-color">Colour</label>
        <input type="text" id="cat-color" name="color" value="{{ old('color', $category->color) }}" placeholder="#4f46e5">
    </div>
    <div class="field">
        <label for="cat-order">Sort order</label>
        <input type="number" id="cat-order" name="sort_order" value="{{ old('sort_order', $category->sort_order ?? 0) }}">
    </div>
</div>
<details class="field">
    <summary>SEO</summary>
    <div class="field" style="margin-top:10px">
        <label for="cat-mt">Meta title</label>
        <input type="text" id="cat-mt" name="meta_title" value="{{ old('meta_title', $category->meta_title) }}">
    </div>
    <div class="field">
        <label for="cat-md">Meta description</label>
        <textarea id="cat-md" name="meta_description" rows="2">{{ old('meta_description', $category->meta_description) }}</textarea>
    </div>
</details>
<label class="check field"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $category->is_active ?? true))> Visible on the website</label>
