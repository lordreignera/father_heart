<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'website.home')->name('home');
// Keep existing public URLs working while the website lives on one page.
Route::redirect('/about', '/#about')->name('website.about');
Route::redirect('/our-work', '/#work')->name('website.work');
Route::redirect('/stories', '/#stories')->name('website.stories');
Route::redirect('/support', '/#give')->name('website.support');
Route::redirect('/contact', '/#contact')->name('website.contact');

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');
});
