<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    // ID tidak auto-increment — dipakai ID yang sama dari Gateway
    public $incrementing = false;

    protected $fillable = [
        'id',
        'name',
        'email',
        'role',
    ];

    public function courses()
    {
        return $this->hasMany(Course::class, 'instructor_id');
    }

    public function enrollments()
    {
        return $this->hasMany(Enrollment::class);
    }
}
