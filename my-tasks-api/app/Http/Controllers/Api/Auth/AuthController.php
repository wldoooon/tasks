<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api', ['except' => ['login', 'register']]);
    }

    /**
     * Get a JWT via given credentials.
     *
     * @param  Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request)
    {
        $email = strtolower(trim($request->input('email')));
        $password = $request->input('password');

        if (empty($email)) {
            return response()->json([
                'success' => false,
                'message' => 'Email is required',
                'field_errors' => ['email' => 'Email address is required']
            ], 422);
        }

        if (empty($password)) {
            return response()->json([
                'success' => false,
                'message' => 'Password is required',
                'field_errors' => ['password' => 'Password is required']
            ], 422);
        }

        try {
            if (!$token = auth('api')->attempt(['email' => $email, 'password' => $password])) {
                $user = User::where('email', $email)->first();

                if (!$user) {
                    Log::warning('Login attempt with non-existent email', [
                        'email' => $email,
                        'ip' => $request->ip(),
                        'user_agent' => $request->userAgent()
                    ]);

                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid credentials',
                        'field_errors' => ['email' => 'We couldn\'t find an account with these credentials. Please check your email or register if you don\'t have an account.']
                    ], 401);
                } else {
                    Log::warning('Login attempt with incorrect password', [
                        'user_id' => $user->id,
                        'email' => $user->email,
                        'ip' => $request->ip(),
                        'user_agent' => $request->userAgent()
                    ]);

                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid credentials',
                        'field_errors' => ['password' => 'The credentials you provided don\'t match our records. Please check your password and try again.']
                    ], 401);
                }
            }
        } catch (\Exception $e) {
            Log::error('Authentication error', [
                'email' => $email,
                'ip' => $request->ip(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Authentication failed',
                'error' => 'An error occurred during authentication.'
            ], 500);
        }

        $user = auth('api')->user();
        Log::info('User logged in successfully', [
            'user_id' => $user->id,
            'email' => $user->email,
            'ip' => $request->ip()
        ]);

        return $this->respondWithToken($token);
    }

    /**
     * Register a User.
     *
     * @param  Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function register(Request $request)
    {
        $rules = [
            'name' => 'required|string|between:2,20|regex:/^[a-zA-Z\s]+$/',
            'email' => 'required|string|email|max:100|unique:users,email',
            'password' => 'required|string|confirmed|min:8|regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/',
        ];

        $messages = [
            'name.required' => 'Full name is required and cannot be empty.',
            'name.string' => 'Name must be a valid text string.',
            'name.between' => 'Name must be between 2 and 100 characters long.',
            'name.regex' => 'Name can only contain letters and spaces.',

            'email.required' => 'Email address is required and cannot be empty.',
            'email.string' => 'Email must be a valid text string.',
            'email.email' => 'Please enter a valid email address format (e.g., user@example.com).',
            'email.max' => 'Email address is too long. Maximum 100 characters allowed.',
            'email.unique' => 'This email address is already registered. Please use a different email or try logging in.',

            'password.required' => 'Password is required and cannot be empty.',
            'password.string' => 'Password must be a valid text string.',
            'password.min' => 'Password must be at least 8 characters long.',
            'password.confirmed' => 'Password confirmation does not match. Please re-enter both passwords.',
            'password.regex' => 'Password must contain at least one uppercase letter, one lowercase letter, and one number.',
        ];

        $customAttributes = [
            'name' => 'full name',
            'email' => 'email address',
            'password' => 'password',
            'password_confirmation' => 'password confirmation',
        ];

        $validator = Validator::make($request->all(), $rules, $messages, $customAttributes);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Registration failed due to validation errors.',
                'errors' => $validator->errors(),
                'error_count' => $validator->errors()->count(),
            ], 422); // 
        }

        try {
            $user = User::create([
                'name' => trim($request->name),
                'email' => strtolower(trim($request->email)),
                'password' => Hash::make($request->password),
                'created_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Registration successful! Welcome to our platform.',
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'created_at' => $user->created_at,
                    ]
                ]
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Registration failed due to server error.',
                'error' => 'An unexpected error occurred. Please try again later.'
            ], 500);
        }
    }


    /**
     * Log the user out (Invalidate the token).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout()
    {
        auth('api')->logout();

        return response()->json(['message' => 'Successfully logged out']);
    }

    /**
     * Refresh a token.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function refresh()
    {
        try {
            $newToken = auth('api')->refresh();
            return $this->respondWithToken($newToken);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Token refresh failed',
                'error' => $e->getMessage()
            ], 401);
        }
    }

    /**
     * Get the authenticated User.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function userProfile()
    {
        return response()->json(auth('api')->user());
    }

    /**
     * Get the token array structure.
     *
     * @param  string $token
     * @param  int $status
     * @return \Illuminate\Http\JsonResponse
     */
    protected function respondWithToken($token, $status = 200)
    {
        $user = auth('api')->user();
        $ttl = auth('api')->factory()->getTTL(); 

        return response()->json([
            'success' => true,
            'message' => $status === 201 ? 'Registration successful' : 'Login successful',
            'data' => [
                'access_token' => $token,
                'token_type' => 'bearer',
                'expires_in_seconds' => $ttl * 60,
                'expires_at' => Carbon::now()->addMinutes($ttl)->toISOString(),
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'email_verified_at' => $user->email_verified_at,
                    'created_at' => $user->created_at->toISOString(),
                    'last_login_at' => $user->last_login_at ? $user->last_login_at->toISOString() : null
                ]
            ]
        ], $status);
    }
}
