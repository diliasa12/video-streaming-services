<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class InternalController extends Controller
{
  /**
   * POST /internal/users
   * Dipanggil oleh Gateway saat user register/update.
   * Sync user mirror ke database Course Service.
   */
  public function syncUser(Request $request): JsonResponse
  {
    // Validasi internal secret
    if ($request->header('X-Internal-Secret') !== env('INTERNAL_SECRET')) {
      return response()->json(['message' => 'Forbidden'], 403);
    }

    $this->validate($request, [
      'id'    => 'required|integer',
      'name'  => 'required|string',
      'email' => 'required|email',
      'role'  => 'required|string',
    ]);

    // Upsert — buat jika belum ada, update jika sudah ada
    $user = User::updateOrCreate(
      ['id' => $request->id],
      [
        'name'  => $request->name,
        'email' => $request->email,
        'role'  => $request->role,
      ]
    );

    return response()->json([
      'success' => true,
      'data'    => $user,
    ], 201);
  }
}
