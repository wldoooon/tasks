<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validateData = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = user::create([
            'name' => $validateData['name'],
            'email' => $validateData['email'],
            'password' => $validateData['password'],
        ]);

        return response()->json([
            'message' => 'User registred Succesfully',
            'user' => $user,
        ], 201);

    }

    public function login(Request $request)
    {

    }


}
