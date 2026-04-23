<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\LessonProgress;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Carbon\Carbon;

class CourseController extends Controller
{
  // ─────────────────────────────────────────────
  // GET /courses
  // ─────────────────────────────────────────────
  public function index(Request $request): JsonResponse
  {
    $userId = (int) $request->header('X-User-Id');

    $query = Course::query()->where('is_published', true);

    if ($search = $request->query('search')) {
      $query->where('title', 'LIKE', "%{$search}%");
    }

    $courses = $query
      ->withCount('lessons')          // total lesson per kursus
      ->withCount('enrollments')      // total peserta
      ->orderByDesc('created_at')
      ->paginate((int) $request->query('per_page', 10));

    // Tandai kursus mana yang sudah dienroll user ini
    $enrolledCourseIds = Enrollment::where('user_id', $userId)
      ->where('status', 'active')
      ->pluck('course_id')
      ->toArray();

    $items = collect($courses->items())->map(function ($course) use ($enrolledCourseIds) {
      $course->is_enrolled = in_array($course->id, $enrolledCourseIds);
      return $course;
    });

    return response()->json([
      'success' => true,
      'data'    => $items,
      'meta'    => [
        'total'        => $courses->total(),
        'per_page'     => $courses->perPage(),
        'current_page' => $courses->currentPage(),
        'last_page'    => $courses->lastPage(),
      ],
    ]);
  }

  // ─────────────────────────────────────────────
  // POST /courses
  // ─────────────────────────────────────────────
  public function store(Request $request): JsonResponse
  {
    if (!in_array($request->header('X-User-Role'), ['instructor', 'admin'])) {
      return response()->json(['success' => false, 'message' => 'Akses ditolak.'], 403);
    }

    $this->validate($request, [
      'title'         => 'required|string|max:255',
      'description'   => 'nullable|string',
      'price'         => 'nullable|numeric|min:0',
      'thumbnail_url' => 'nullable|url',
      'is_published'  => 'nullable|boolean',
    ]);

    $instructorId = (int) $request->header('X-User-Id');

    // Pastikan slug unik
    $slug = $this->uniqueSlug($request->title);

    $course = Course::create([
      'instructor_id' => $instructorId,
      'title'         => $request->title,
      'slug'          => $slug,
      'description'   => $request->description,
      'thumbnail_url' => $request->thumbnail_url,
      'price'         => $request->price ?? 0,
      'is_published'  => $request->boolean('is_published', false),
    ]);

    return response()->json([
      'success' => true,
      'message' => 'Kursus berhasil dibuat.',
      'data'    => $course,
    ], 201);
  }

  // ─────────────────────────────────────────────
  // GET /courses/:id/lessons
  // ─────────────────────────────────────────────
  public function lessons(Request $request, int $id): JsonResponse
  {
    $course = Course::find($id);

    if (!$course) {
      return $this->notFound('Kursus');
    }

    $userId  = (int) $request->header('X-User-Id');
    $lessons = $course->lessons()->get();

    // Ambil progress user untuk semua lesson sekaligus (hindari N+1)
    $progressMap = LessonProgress::where('user_id', $userId)
      ->whereIn('lesson_id', $lessons->pluck('id'))
      ->get()
      ->keyBy('lesson_id');

    $data = $lessons->map(function ($lesson) use ($progressMap, $userId) {
      $progress = $progressMap->get($lesson->id);
      return [
        'id'               => $lesson->id,
        'title'            => $lesson->title,
        'video_id'         => $lesson->video_id,
        'order'            => $lesson->order,
        'duration_sec'     => $lesson->duration_sec,
        'is_preview'       => $lesson->is_preview,
        // Progress user untuk lesson ini
        'is_completed'     => $progress?->is_completed ?? false,
        'last_position_sec' => $progress?->last_position_sec ?? 0,
        'completed_at'     => $progress?->completed_at,
      ];
    });

    return response()->json([
      'success'       => true,
      'course_title'  => $course->title,
      'total_lessons' => $lessons->count(),
      'data'          => $data,
    ]);
  }

  // ─────────────────────────────────────────────
  // POST /courses/:id/enroll
  // ─────────────────────────────────────────────
  public function enroll(Request $request, int $id): JsonResponse
  {
    $course = Course::find($id);

    if (!$course) {
      return $this->notFound('Kursus');
    }

    if (!$course->is_published) {
      return response()->json([
        'success' => false,
        'message' => 'Kursus belum tersedia.',
      ], 400);
    }

    $userId = (int) $request->header('X-User-Id');

    // Cek apakah sudah enroll
    $existing = Enrollment::where('user_id', $userId)
      ->where('course_id', $id)
      ->first();

    if ($existing) {
      return response()->json([
        'success' => false,
        'message' => 'Kamu sudah terdaftar di kursus ini.',
        'data'    => $existing,
      ], 409);
    }

    $enrollment = Enrollment::create([
      'user_id'     => $userId,
      'course_id'   => $id,
      'status'      => 'active',
      'enrolled_at' => Carbon::now(),
    ]);

    return response()->json([
      'success' => true,
      'message' => "Berhasil enroll ke kursus \"{$course->title}\".",
      'data'    => $enrollment->load('course'),
    ], 201);
  }

  // ─────────────────────────────────────────────
  // GET /progress/:userId
  // ─────────────────────────────────────────────
  public function progress(Request $request, int $userId): JsonResponse
  {
    // Ambil semua enrollment aktif milik user
    $enrollments = Enrollment::with('course')
      ->where('user_id', $userId)
      ->where('status', 'active')
      ->get();

    if ($enrollments->isEmpty()) {
      return response()->json([
        'success' => true,
        'message' => 'User belum enroll ke kursus apapun.',
        'data'    => [],
      ]);
    }

    $courseIds = $enrollments->pluck('course_id');

    // Ambil semua lesson dari semua kursus sekaligus
    $allLessons = Lesson::whereIn('course_id', $courseIds)
      ->get()
      ->groupBy('course_id');

    // Ambil semua progress user sekaligus
    $allProgress = LessonProgress::where('user_id', $userId)
      ->whereIn(
        'lesson_id',
        $allLessons->flatten()->pluck('id')
      )
      ->where('is_completed', true)
      ->get()
      ->pluck('lesson_id')
      ->toArray();

    $data = $enrollments->map(function ($enrollment) use ($allLessons, $allProgress) {
      $lessons       = $allLessons->get($enrollment->course_id, collect());
      $totalLessons  = $lessons->count();
      $completedLessons = $lessons->filter(
        fn($lesson) => in_array($lesson->id, $allProgress)
      )->count();

      return [
        'course_id'         => $enrollment->course_id,
        'course_title'      => $enrollment->course->title,
        'course_thumbnail'  => $enrollment->course->thumbnail_url,
        'enrolled_at'       => $enrollment->enrolled_at,
        'total_lessons'     => $totalLessons,
        'completed_lessons' => $completedLessons,
        'percentage'        => $totalLessons > 0
          ? round($completedLessons / $totalLessons * 100, 1)
          : 0,
        'is_finished'       => $totalLessons > 0 && $completedLessons === $totalLessons,
      ];
    });

    return response()->json([
      'success'          => true,
      'user_id'          => $userId,
      'total_courses'    => $enrollments->count(),
      'data'             => $data,
    ]);
  }

  // ─────────────────────────────────────────────
  // POST /lessons/:id/complete
  // ─────────────────────────────────────────────
  public function completeLesson(Request $request, int $id): JsonResponse
  {
    $this->validate($request, [
      'last_position_sec' => 'nullable|integer|min:0',
    ]);

    $lesson = Lesson::find($id);

    if (!$lesson) {
      return $this->notFound('Lesson');
    }

    $userId = (int) $request->header('X-User-Id');

    // Pastikan user sudah enroll ke kursus ini
    $isEnrolled = Enrollment::where('user_id', $userId)
      ->where('course_id', $lesson->course_id)
      ->where('status', 'active')
      ->exists();

    if (!$isEnrolled) {
      return response()->json([
        'success' => false,
        'message' => 'Kamu belum terdaftar di kursus ini.',
      ], 403);
    }

    // updateOrCreate — buat baru jika belum ada, update jika sudah
    $progress = LessonProgress::updateOrCreate(
      [
        'user_id'   => $userId,
        'lesson_id' => $id,
      ],
      [
        'is_completed'      => true,
        'last_position_sec' => $request->input('last_position_sec', 0),
        'completed_at'      => Carbon::now(),
      ]
    );

    // Hitung ulang total progress kursus
    $totalLessons     = Lesson::where('course_id', $lesson->course_id)->count();
    $completedLessons = LessonProgress::where('user_id', $userId)
      ->whereHas('lesson', fn($q) => $q->where('course_id', $lesson->course_id))
      ->where('is_completed', true)
      ->count();

    return response()->json([
      'success'           => true,
      'message'           => 'Lesson ditandai selesai.',
      'data'              => [
        'lesson_id'         => $id,
        'is_completed'      => true,
        'completed_at'      => $progress->completed_at,
        'course_progress'   => [
          'completed_lessons' => $completedLessons,
          'total_lessons'     => $totalLessons,
          'percentage'        => $totalLessons > 0 ? round($completedLessons / $totalLessons * 100, 1) : 0,
          'is_finished'       => $completedLessons === $totalLessons,
        ],
      ],
    ]);
  }

  // ─────────────────────────────────────────────
  // Helpers
  // ─────────────────────────────────────────────
  private function notFound(string $resource): JsonResponse
  {
    return response()->json([
      'success' => false,
      'message' => "{$resource} tidak ditemukan.",
    ], 404);
  }

  private function uniqueSlug(string $title): string
  {
    $slug  = Str::slug($title);
    $count = Course::where('slug', 'LIKE', "{$slug}%")->count();
    return $count > 0 ? "{$slug}-{$count}" : $slug;
  }
}
