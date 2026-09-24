<?php

namespace Vitebox\LaravelBlog\Exceptions;

use RuntimeException;
use Vitebox\LaravelBlog\Enums\PostStatus;

class InvalidTransition extends RuntimeException
{
    public static function make(string $action, PostStatus $from): self
    {
        return new self("A post that is \"{$from->label()}\" cannot be {$action}.");
    }
}
