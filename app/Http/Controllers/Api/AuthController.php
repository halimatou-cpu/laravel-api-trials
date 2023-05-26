<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

// use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    /**
     * User model
     *
     * @var User
     */
    private User $user;

    public function __construct(User $user)
    {
        $this->user = $user;
    }

    /**
     * Register a user
     *
     * @param Request $request
     * @return json
     */
    public function register(Request $request)
    {
        $this->validate($request, [
            'name' => 'required|string|min:2|max:255',
            'email' => 'required|string|email:rfc,dns|unique:users|string',
            'password' => 'required|string|min:8|max:255',
        ]);

        $user = $this->user::create([
            'name' => $request->name,
            'email' => $request->email,
            // 'password' => Hash::make($request->password),
            'password' => bcrypt($request->password),
        ]);

        $token = auth()->login($user);
        return response()->json([
            'meta' => [
                'code' => 201,
                'status' => 'created',
                'message' => 'User registered successfully',
            ],
            'data' => [
                'user' => $user,
                'access_token' => [
                    'token' => $token,
                    'type' => 'Bearer',
                    'expires_in' => auth()->factory()->getTTL() * 60, // get token expires in seconds
                ]
            ]
        ]);
    }

    /**
     * Login a user
     *
     * @param Request $request
     * @return json
     */
    public function login(Request $request)
    {
        $this->validate($request, [
            'email' => 'required|string|email:rfc,dns',
            'password' => 'required|string',
        ]);

        $token = auth()->attempt([
            'email' => $request->email,
            'password' => $request->password,
        ]);

        if (isset($token)) {
            return response()->json([
                'meta' => [
                    'code' => 200,
                    'status' => 'success',
                    'message' => 'User logged in successfully',
                ],
                'data' => [
                    'user' => auth()->user(),
                    'access_token' => [
                        'token' => $token,
                        'type' => 'Bearer',
                        'expires_in' => auth()->factory()->getTTL() * 60,
                    ],
                ],
            ]);
        }
    }

    /**
     * Logout a user
     *
     * @param Request $request
     * @return json
     */
    public function logout()
    {
        $token = JWTAuth::getToken();

        $invalidate = JWTAuth::invalidate($token);

        if (isset($invalidate)) {
            return response()->json([
                'meta' => [
                    'code' => 200,
                    'status' => 'success',
                    'message' => 'User logged out successfully',
                ],
                'data' => [],
            ]);
        }
    }
}
