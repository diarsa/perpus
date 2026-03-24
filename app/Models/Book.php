<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Book extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'author_id',
        'publisher_id',
        'classification_id',
        'published_year',
        'isbn',
        'description',
        'cover_image',
        'stock',
        'available_stock',
    ];

    public function author()
    {
        return $this->belongsTo(Author::class);
    }

    public function publisher()
    {
        return $this->belongsTo(Publisher::class);
    }

    public function classification()
    {
        return $this->belongsTo(Classification::class);
    }

    public function borrowings()
    {
        return $this->hasMany(Borrowing::class);
    }
}
