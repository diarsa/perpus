<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Traits\LogsActivity;

class Student extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'user_id',
        'nis',
        'password',
        'name',
        'class',
        'gender',
        'phone',
        'address',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function borrowings()
    {
        return $this->hasMany(Borrowing::class);
    }
}
