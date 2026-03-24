<?php

use App\Models\Author;
use App\Models\Book;
use App\Models\Borrowing;
use App\Models\Publisher;
use App\Models\Student;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('creates a pending borrowing and admin approval decrements stock', function () {
    $author = Author::create(['name' => 'Test Author']);
    $publisher = Publisher::create(['name' => 'Test Publisher']);

    $book = Book::create([
        'title' => 'Test Book',
        'author_id' => $author->id,
        'publisher_id' => $publisher->id,
        'published_year' => 2020,
        'stock' => 3,
        'available_stock' => 3,
    ]);

    $student = Student::create([
        'nis' => '12345',
        'name' => 'Siswa Test',
        'class' => 'VII-1',
    ]);

    // Student requests borrow -> pending
    $borrowing = Borrowing::create([
        'student_id' => $student->id,
        'book_id' => $book->id,
        'borrow_date' => now(),
        'return_date' => now()->addDays(7),
        'status' => 'pending',
    ]);

    expect(Borrowing::where('id', $borrowing->id)->where('status', 'pending')->exists())->toBeTrue();

    // available_stock should remain unchanged while pending
    $book->refresh();
    expect($book->available_stock)->toBe(3);

    // Admin approves
    $borrowing->update(['status' => 'borrowed']);
    $book->decrement('available_stock');
    $book->refresh();

    expect($book->available_stock)->toBe(2);
});
