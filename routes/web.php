<?php

use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Volt::route('/', 'welcome')->name('home');

Volt::route('dashboard', 'admin.dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Route::prefix('settings')->group(function () {  
        Volt::route('profile', 'settings.profile')->name('settings.profile');
        Volt::route('password', 'settings.password')->name('settings.password');
        Volt::route('appearance', 'settings.appearance')->name('settings.appearance');
        Volt::route('activity-log', 'settings.activity-log')->name('settings.activity-log');
    });

    Route::prefix('admin')->group(function () {
        Volt::route('books', 'admin.books')->name('admin.books');
        Volt::route('authors', 'admin.authors')->name('admin.authors');
        Volt::route('publishers', 'admin.publishers')->name('admin.publishers');
        Volt::route('classifications', 'admin.classifications')->name('admin.classifications');
        Volt::route('students', 'admin.students')->name('admin.students');
        Volt::route('borrowings', 'admin.borrowings')->name('admin.borrowings');
    });
});

require __DIR__.'/auth.php';
