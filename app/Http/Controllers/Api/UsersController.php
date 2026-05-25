<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;

class UsersController extends Controller
{
    public function index()
    {
        return response()->json([
            'status' => 'success',
            'data' => User::all(),
        ]);
    }
}
