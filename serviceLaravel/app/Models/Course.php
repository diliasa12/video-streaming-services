<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Course extends Model
{
  protected $fillable = [
    'instructor_id',
    'title',
    'slug',
    'description',
    'thumbnail_url',
    'price',
    'is_published',
  ];

  protected $casts = [
    'price'        => 'float',
    'is_published' => 'boolean',
  ];

  public function lessons()
  {
    return $this->hasMany(Lesson::class)->orderBy('order');
  }

  public function enrollments()
  {
    return $this->hasMany(Enrollment::class);
  }

  public function instructor()
  {
    return $this->belongsTo(User::class, 'instructor_id');
  }

  // Cek apakah user sudah enroll
  public function isEnrolledBy(int $userId): bool
  {
    return $this->enrollments()
      ->where('user_id', $userId)
      ->where('status', 'active')
      ->exists();
  }
}
