<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\JwtService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use RuntimeException;

class AuthController extends Controller
{
    public function register(Request $request, JwtService $jwt)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $user = new User();
        $user->name = $validated['name'];
        $user->email = $validated['email'];
        $user->password = Hash::make($validated['password']);
        $user->role = 'learner';
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Registration successful.',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
            ],
            'token' => $jwt->createToken($user),
            'token_type' => 'Bearer',
            'expires_in' => (int) config('jwt.ttl', 3600),
        ], 201);
    }

    public function login(Request $request, JwtService $jwt)
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid email or password.',
            ], 401);
        }

        return response()->json([
            'success' => true,
            'message' => 'Login successful.',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
            ],
            'token' => $jwt->createToken($user),
            'token_type' => 'Bearer',
            'expires_in' => (int) config('jwt.ttl', 3600),
        ]);
    }

    public function logout(Request $request, JwtService $jwt)
    {
        $payload = $request->attributes->get('jwt_payload');

        if (! is_array($payload)) {
            return response()->json([
                'success' => false,
                'message' => 'Authentication required.',
            ], 401);
        }

        try {
            $jwt->revokeToken($payload);
        } catch (RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Could not revoke token.',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully.',
        ]);
    }
}
