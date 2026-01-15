<?php

namespace App\Http\Controllers;

use App\Models\Admin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|min:8'
        ]);

        $admin = Admin::where('email', $request->email)->first();

        if (!$admin || !Hash::check($request->password, $admin->password)) {
            throw ValidationException::withMessages([
                'email' => ['As credenciais fornecidas estão incorretas']
            ]);
        }

        $admin->tokens()->delete();

        $token = $admin->createToken('auth_token')->plainTextToken;

        return response()->json([
            'admin' => $admin->only(['id', 'name', 'email', 'role', 'avatar', 'slug']),
            'token' => $token,
            'token_type' => 'Bearer'
        ]);
    }

    public function register(Request $request)
    {
        if (!auth()->user()->isSuperAdmin()) {
            return response()->json(['message' => 'Apenas superadministradores podem criar novos usuários.'], 401);
        }
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:admins',
            'password' => 'required|min:8|string|confirmed',
            'role' => 'required|in:superadmin,author',
            'bio' => 'nullable|string',
            'slug' => 'nullable|string|unique:admins'
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        $admin = Admin::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'bio' => $request->bio,
            'slug' => $request->slug ?? \Illuminate\Support\Str::slug($request->name)
        ]);

        return response()->json([
            'message' => 'Usuário criado com sucesso',
            'admin' => $admin
        ], 201);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logout realizado'
        ]);
    }
    public function me(Request $request)
    {
        $admin = $request->user();
        $admin->append('avatar_url');

        return response()->json(
            $admin->loadCount([
                'news as published_news_count' => function ($query) {
                    $query->where('is_published', true);
                }
            ])
        );
    }
    public function refresh(Request $request)
    {
        $admin = $request->user();
        $admin->tokens()->delete();

        $token = $admin->createToken('auth_token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'token_type' => 'Bearer'
        ]);
    }
}
