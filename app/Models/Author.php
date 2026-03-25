<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use App\Traits\LogsActivity;

class Author extends Model
{
    use LogsActivity;
    protected $fillable = ['name', 'biography'];

    public function books()
    {
        return $this->hasMany(Book::class);
    }
}
