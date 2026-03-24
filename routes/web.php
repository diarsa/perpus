<?php

use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Volt::route('/', 'welcome')->name('home');

Volt::route('dashboard', 'admin.dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Volt::route('settings/profile', 'settings.profile')->name('settings.profile');
    Volt::route('settings/password', 'settings.password')->name('settings.password');
    Volt::route('settings/appearance', 'settings.appearance')->name('settings.appearance');

    Volt::route('admin/books', 'admin.books')->name('admin.books');
    Volt::route('admin/authors', 'admin.authors')->name('admin.authors');
    Volt::route('admin/publishers', 'admin.publishers')->name('admin.publishers');
    Volt::route('admin/classifications', 'admin.classifications')->name('admin.classifications');
    Volt::route('admin/students', 'admin.students')->name('admin.students');
    Volt::route('admin/borrowings', 'admin.borrowings')->name('admin.borrowings');
});

require __DIR__.'/auth.php';
