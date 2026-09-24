@php
    $name = 'fields['.$field->name.']';
    $key = 'fields.'.$field->name;
    $id = 'f_'.$field->name.'_'.substr(md5($name.($active ? '1' : '0').spl_object_id($field)), 0, 6);
    $current = $post->exists ? $post->field($field->name) : $field->default;
    $value = $active ? old($key, $current) : $current;
    if ($field->input === 'datetime' && $value) {
        $value = \Illuminate\Support\Carbon::parse($value)->format('Y-m-d\TH:i');
    }
    $wide = in_array($field->input, ['textarea'], true);
@endphp

<div class="field" @if($wide) style="grid-column:1/-1" @endif>
    @if($field->input === 'checkbox')
        <input type="hidden" name="{{ $name }}" value="0" @disabled(!$active)>
        <label class="check" for="{{ $id }}">
            <input type="checkbox" id="{{ $id }}" name="{{ $name }}" value="1" @checked((bool) $value) @disabled(!$active)>
            {{ $field->label }}
        </label>
    @else
        <label for="{{ $id }}">{{ $field->label }} @if($field->required)<span class="muted small">(required)</span>@endif</label>

        @switch($field->input)
            @case('textarea')
                <textarea id="{{ $id }}" name="{{ $name }}" rows="4" @disabled(!$active) @class(['invalid' => $errors->has($key)])>{{ $value }}</textarea>
                @break
            @case('select')
                <select id="{{ $id }}" name="{{ $name }}" @disabled(!$active) @class(['invalid' => $errors->has($key)])>
                    @unless($field->required)<option value="">—</option>@endunless
                    @foreach($field->options as $optValue => $optLabel)
                        <option value="{{ $optValue }}" @selected((string) $value === (string) $optValue)>{{ $optLabel }}</option>
                    @endforeach
                </select>
                @break
            @default
                <input id="{{ $id }}" name="{{ $name }}" value="{{ $value }}"
                       type="{{ ['datetime' => 'datetime-local', 'number' => 'number', 'url' => 'url', 'email' => 'email'][$field->input] ?? 'text' }}"
                       @if($field->placeholder) placeholder="{{ $field->placeholder }}" @endif
                       @disabled(!$active) @class(['invalid' => $errors->has($key)])>
        @endswitch
    @endif

    @if($field->help)<div class="help">{{ $field->help }}</div>@endif
    @error($key)<div class="error">{{ $message }}</div>@enderror
</div>
