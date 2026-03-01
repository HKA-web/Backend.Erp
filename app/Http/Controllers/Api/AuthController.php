<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Modules\Authentication\Models\User;

class AuthController extends Controller
{
    /**
     * REGISTER USER
     * Menghasilkan token API menggunakan Sanctum
     */
    public function register(Request $request)
    {
        // Menggunakan wrapper erpExecution milikmu
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

            // Create User dengan UUID
            $user = User::create([
                'user_id'    => (string) Str::uuid(),
                'user_name'  => $request->user_name,
                'email'      => $request->email,
                'password'   => Hash::make($request->password)
            ]);

            // createToken() sekarang aman karena Model sudah Authenticatable
            $token = $user->createToken('api-token')->plainTextToken;

            return response()->json([
                'message' => 'User registered successfully',
                'access'  => $token,
                'user'    => $user
            ], 201);
        });
    }

    /**
     * LOGIN USER
     * Stateless authentication menggunakan Sanctum Token
     */
    public function login(Request $request)
    {
        return $this->erpExecution(function () use ($request) {
            $request->validate([
                'email'    => 'required|email',
                'password' => 'required'
            ]);

            // Cari user berdasarkan email
            $user = User::where('email', $request->email)->first();

            // Validasi Password
            if (!$user || !Hash::check($request->password, $user->password)) {
                return response()->json(['error' => 'Invalid credentials'], 401);
            }

            // Generate Sanctum Token
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
                // Pastikan model User sudah menggunakan trait Spatie HasRoles jika ingin pakai ini
                'permissions' => method_exists($user, 'getAllPermissions')
                    ? $user->getAllPermissions()->pluck('name')
                    : []
            ]);
        });
    }

    /**
     * LOGOUT
     * Menghapus token yang sedang digunakan (Stateless)
     */
    public function logout(Request $request)
    {
        // Mengambil user dari request yang sudah ter-autentikasi middleware sanctum
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully'
        ]);
    }

    /**
     * PROFILE
     * Melihat info user saat ini
     */
    public function profile(Request $request)
    {
        return response()->json($request->user());
    }
}
