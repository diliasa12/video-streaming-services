<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LessonProgress extends Model
{
  public $timestamps = false;

  protected $table = 'lesson_progress';

  protected $fillable = [
    'user_id',
    'lesson_id',
    'is_completed',
    'last_position_sec',
    'completed_at',
  ];

  protected $casts = [
    'is_completed'     => 'boolean',
    'last_position_sec' => 'integer',
    'completed_at'     => 'datetime',
  ];

  public function lesson()
  {
    return $this->belongsTo(Lesson::class);
  }
}
