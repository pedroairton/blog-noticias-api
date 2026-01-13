<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Admin;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class AdminController extends Controller
{
    public function index()
    {
        $authors = Admin::where('role', 'author')
            ->select(
                'name',
                'email',
                'avatar',
                'bio',
                'website',
                'social_facebook',
                'social_twitter',
                'social_instagram',
                'social_linkedin'
            )
            ->paginate(20);

        $authors->each(function ($author) {
            $author->append('avatar_url');
        });
        $admins = Admin::where('role', 'superadmin')
            ->select(
                'name',
                'email',
                'avatar',
                'bio',
                'website',
                'social_facebook',
                'social_twitter',
                'social_instagram',
                'social_linkedin'
            )
            ->paginate(20);
        $admins->each(function ($admin) {
            $admin->append('avatar_url');
        });
        return response()->json(['authors' => $authors, 'admins' => $admins], 200);
    }
    public function profile()
    {
        // dd(auth()->user());
        $admin = auth()->user();
        return response()->json($admin);
    }
    public function store(Request $request){

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:admins',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'required|string|in:author,superadmin'
        ], [
            'name.required' => 'O nome é obrigatório',
            'email.required' => 'O email é obrigatório',
            'email.email' => 'O email é inválido',
            'password.required' => 'A senha é obrigatória',
            'password.min' => 'A senha deve ter no mínimo 8 caracteres',
            'password.confirmed' => 'As senhas não conferem',
            'role.required' => 'O cargo é obrigatório',
            'role.in' => 'O cargo especificado é inválido',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $admin = Admin::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'slug' => Str::slug($request->name),
            'status' => true
        ]);

        return response()->json(['message' => 'Admin criado com sucesso'], 201);
    }
}
