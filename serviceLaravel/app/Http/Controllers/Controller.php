<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Laravel\Lumen\Routing\Controller as BaseController;

class Controller extends BaseController
{
    public function getHello()
    {
        $user = DB::select('select * from users');
        if ($user == null) {
            return response()->json(["success" => "failed", "message" => "user not found"], 404);
        }
        return response()->json(["message" => "hello", "id" => 1], 201, ["Authorization" => "Bearer ...."]);
    }
    public function getCourse(): JsonResponse
    {
        $courses = DB::select('select * from courses');
        if ($courses == null) {
            return response()->json(["success" => false, "message" => "Courses empty"], 404);
        }
        return response()->json(["success" => true, "courses" => $courses]);
    }
    public function createCourse(Request $request)
    {
        $role = $request->header('X-User-Role');
        if ($role == "Student") {
            return response()->json(["success" => false, "message" => "Forbidden Access"], 403);
        }
        $data = DB::insert('insert into courses values ?', [$request]);
        return response()->json(["success" => true, "data" => $data]);
    }
}
