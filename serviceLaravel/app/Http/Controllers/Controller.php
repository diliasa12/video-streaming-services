<?php

namespace App\Http\Controllers;

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
}
