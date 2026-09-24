<?php

namespace Vitebox\LaravelBlog\PostTypes;

/**
 * A type-specific form field. Values are stored in the post's `type_data`
 * JSON column, or in a real column when ->column() is used (so they can be
 * queried efficiently, e.g. event start dates).
 */
class Field
{
    public bool $required = false;
    public ?string $column = null;
    public ?string $help = null;
    public mixed $default = null;
    /** @var array<string, string> */
    public array $options = [];
    /** @var array<int, mixed> */
    public array $extraRules = [];
    public ?string $placeholder = null;

    final public function __construct(
        public readonly string $name,
        public readonly string $label,
        public readonly string $input = 'text',
    ) {
    }

    public static function text(string $name, string $label): static { return new static($name, $label, 'text'); }
    public static function textarea(string $name, string $label): static { return new static($name, $label, 'textarea'); }
    public static function url(string $name, string $label): static { return new static($name, $label, 'url'); }
    public static function email(string $name, string $label): static { return new static($name, $label, 'email'); }
    public static function number(string $name, string $label): static { return new static($name, $label, 'number'); }
    public static function datetime(string $name, string $label): static { return new static($name, $label, 'datetime'); }
    public static function checkbox(string $name, string $label): static { return new static($name, $label, 'checkbox'); }

    /** @param array<string, string> $options */
    public static function select(string $name, string $label, array $options): static
    {
        $f = new static($name, $label, 'select');
        $f->options = $options;

        return $f;
    }

    public function required(bool $required = true): static { $this->required = $required; return $this; }
    public function column(string $column): static { $this->column = $column; return $this; }
    public function help(string $help): static { $this->help = $help; return $this; }
    public function default(mixed $value): static { $this->default = $value; return $this; }
    public function placeholder(string $value): static { $this->placeholder = $value; return $this; }
    /** @param array<int, mixed> $rules */
    public function rules(array $rules): static { $this->extraRules = array_merge($this->extraRules, $rules); return $this; }

    /** @return array<int, mixed> */
    public function validationRules(): array
    {
        $base = match ($this->input) {
            'url' => ['url', 'max:2048'],
            'email' => ['email', 'max:255'],
            'number' => ['numeric'],
            'datetime' => ['date'],
            'checkbox' => ['boolean'],
            'select' => ['in:'.implode(',', array_keys($this->options))],
            'textarea' => ['string', 'max:20000'],
            default => ['string', 'max:500'],
        };

        return array_merge([$this->required ? 'required' : 'nullable'], $base, $this->extraRules);
    }

    public function cast(mixed $value): mixed
    {
        if ($value === '' || $value === null) {
            return $this->input === 'checkbox' ? false : null;
        }

        return match ($this->input) {
            'checkbox' => (bool) $value,
            'number' => is_numeric($value) ? $value + 0 : null,
            default => $value,
        };
    }
}
