<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Modules\Authentication\Models\User;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        return $this->erpExecution(function () use ($request) {
            $request->validate([
                'user_name' => 'required|string|max:255',
                'email' => [
                    'required',
                    'email',
                    Rule::unique('pgsql.authentication.user', 'email'),
                ],
                'password'  => 'required|min:6'
            ]);

            $user = User::create([
                'user_id'    => (string) \Str::uuid(),
                'user_name'  => $request->user_name,
                'email'      => $request->email,
                'password'   => Hash::make($request->password)
            ]);

            $token = $user->createToken('api-token')->plainTextToken;

            return response()->json([
                'message' => 'User registered',
                'access'   => $token,
                'user'    => $user
            ], 201);
        });
    }

    public function login(Request $request)
    {
        return $this->erpExecution(function () use ($request) {
            $request->validate([
                'email'    => 'required|email',
                'password' => 'required'
            ]);

            $user = User::where('email', $request->email)->first();

            if (! $user || ! Hash::check($request->password, $user->password)) {
                return response()->json(['error' => 'Invalid credentials'], 401);
            }

            $token = $user->createToken('api-token')->plainTextToken;

            return response()->json([
                'access' => $token,
                'session' => [
                    'base64pk'        => base64_encode($user->user_id),
                    'user_id'         => (string) $user->user_id,
                    'user_name'       => strtoupper($user->user_name),
                    'real_name'       => $user->user_name,
                    'email'           => $user->email,
                    'phone'           => $user->phone ?? '-',
                ],
                'permissions' => $user->getAllPermissions()->pluck('name') // Contoh ambil dari Spatie
            ]);
        });

    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out'
        ]);
    }

    public function profile(Request $request)
    {
        return response()->json($request->user());
    }
}
