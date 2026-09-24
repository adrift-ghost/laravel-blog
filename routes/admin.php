<?php

use Illuminate\Support\Facades\Route;
use Vitebox\LaravelBlog\Http\Controllers\Admin\CategoryController;
use Vitebox\LaravelBlog\Http\Controllers\Admin\DashboardController;
use Vitebox\LaravelBlog\Http\Controllers\Admin\PostController;
use Vitebox\LaravelBlog\Http\Controllers\Admin\PostWorkflowController;
use Vitebox\LaravelBlog\Http\Controllers\Admin\SlugController;
use Vitebox\LaravelBlog\Http\Controllers\Admin\TagController;
use Vitebox\LaravelBlog\Http\Controllers\Admin\TeamController;

Route::get('/', DashboardController::class)->name('dashboard');

// Posts
Route::get('posts', [PostController::class, 'index'])->name('posts.index');
Route::get('posts/create', [PostController::class, 'create'])->name('posts.create');
Route::post('posts', [PostController::class, 'store'])->name('posts.store');
Route::get('posts/{post}', [PostController::class, 'show'])->name('posts.show')->whereNumber('post');
Route::get('posts/{post}/edit', [PostController::class, 'edit'])->name('posts.edit')->whereNumber('post');
Route::put('posts/{post}', [PostController::class, 'update'])->name('posts.update')->whereNumber('post');
Route::delete('posts/{post}', [PostController::class, 'destroy'])->name('posts.destroy')->whereNumber('post');

// Editorial workflow
Route::get('review', [PostController::class, 'queue'])->name('review.index');
Route::controller(PostWorkflowController::class)->prefix('posts/{post}')->where(['post' => '[0-9]+'])->name('posts.')->group(function () {
    Route::post('submit', 'submit')->name('submit');
    Route::post('approve', 'approve')->name('approve');
    Route::post('request-changes', 'requestChanges')->name('request-changes');
    Route::post('publish', 'publish')->name('publish');
    Route::post('unpublish', 'unpublish')->name('unpublish');
    Route::post('archive', 'archive')->name('archive');
    Route::post('restore', 'restore')->name('restore');
    Route::post('toggle/{flag}', 'toggle')->name('toggle')->whereIn('flag', ['featured', 'pinned']);
});

// Slug preview (AJAX)
Route::get('slug', SlugController::class)->name('slug');

// Categories
Route::get('categories', [CategoryController::class, 'index'])->name('categories.index');
Route::post('categories', [CategoryController::class, 'store'])->name('categories.store');
Route::get('categories/{category}/edit', [CategoryController::class, 'edit'])->name('categories.edit');
Route::put('categories/{category}', [CategoryController::class, 'update'])->name('categories.update');
Route::delete('categories/{category}', [CategoryController::class, 'destroy'])->name('categories.destroy');

// Tags
Route::get('tags', [TagController::class, 'index'])->name('tags.index');
Route::post('tags', [TagController::class, 'store'])->name('tags.store');
Route::get('tags/{tag}/edit', [TagController::class, 'edit'])->name('tags.edit');
Route::put('tags/{tag}', [TagController::class, 'update'])->name('tags.update');
Route::delete('tags/{tag}', [TagController::class, 'destroy'])->name('tags.destroy');
Route::post('tags/merge', [TagController::class, 'merge'])->name('tags.merge');

// Team / roles
Route::get('team', [TeamController::class, 'index'])->name('team.index');
Route::post('team', [TeamController::class, 'store'])->name('team.store');
Route::put('team/{member}', [TeamController::class, 'update'])->name('team.update');
Route::delete('team/{member}', [TeamController::class, 'destroy'])->name('team.destroy');
