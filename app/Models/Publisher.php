<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use App\Traits\LogsActivity;

class Publisher extends Model
{
    use LogsActivity;
    protected $fillable = ['name', 'address'];

    public function books()
    {
        return $this->hasMany(Book::class);
    }
}
