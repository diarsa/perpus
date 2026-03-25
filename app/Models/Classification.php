<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Traits\LogsActivity;

class Classification extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'code',
        'name',
    ];

    public function books()
    {
        return $this->hasMany(Book::class);
    }
}
