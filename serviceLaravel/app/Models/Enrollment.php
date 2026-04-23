<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Enrollment extends Model
{
  public $timestamps = false;

  protected $fillable = [
    'user_id',
    'course_id',
    'status',
    'enrolled_at',
    'expired_at',
  ];

  protected $casts = [
    'enrolled_at' => 'datetime',
    'expired_at'  => 'datetime',
  ];

  public function course()
  {
    return $this->belongsTo(Course::class);
  }

  public function user()
  {
    return $this->belongsTo(User::class);
  }
}
