<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Lesson extends Model
{
  protected $fillable = [
    'course_id',
    'title',
    'video_id',
    'order',
    'duration_sec',
    'is_preview',
  ];

  protected $casts = [
    'order'        => 'integer',
    'duration_sec' => 'integer',
    'is_preview'   => 'boolean',
  ];

  public function course()
  {
    return $this->belongsTo(Course::class);
  }

  public function progress()
  {
    return $this->hasMany(LessonProgress::class);
  }

  // Progress milik satu user tertentu
  public function progressByUser(int $userId)
  {
    return $this->progress()->where('user_id', $userId)->first();
  }
}
