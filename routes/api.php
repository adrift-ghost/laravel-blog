<?php

use Illuminate\Support\Facades\Route;
use Vitebox\LaravelBlog\Http\Controllers\Api\CategoryApiController;
use Vitebox\LaravelBlog\Http\Controllers\Api\PostApiController;
use Vitebox\LaravelBlog\Http\Controllers\Api\TagApiController;

Route::get('types', [PostApiController::class, 'types'])->name('types');

Route::get('posts', [PostApiController::class, 'index'])->name('posts.index');
Route::get('posts/{slug}', [PostApiController::class, 'show'])->name('posts.show');

Route::get('events/upcoming', [PostApiController::class, 'upcomingEvents'])->name('events.upcoming');
Route::get('announcements/active', [PostApiController::class, 'activeAnnouncements'])->name('announcements.active');

Route::get('categories', [CategoryApiController::class, 'index'])->name('categories.index');
Route::get('categories/{slug}', [CategoryApiController::class, 'show'])->name('categories.show');

Route::get('tags', [TagApiController::class, 'index'])->name('tags.index');
Route::get('tags/{slug}', [TagApiController::class, 'show'])->name('tags.show');
