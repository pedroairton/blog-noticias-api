<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Admin;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
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
    public function updatePassword(Request $request){
        $validator = Validator::make($request->all(), [
            'current_password' => 'required|string|min:8',
            'new_password' => 'required|string|min:8|confirmed',
        ], [
            'current_password.required' => 'A senha atual é obrigatória',
            'current_password.min' => 'A senha atual deve ter no mínimo 8 caracteres',
            'new_password.required' => 'A nova senha é obrigatória',
            'new_password.min' => 'A nova senha deve ter no mínimo 8 caracteres',
            'new_password.confirmed' => 'As novas senhas não conferem',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $admin = auth()->user();
        if (!Hash::check($request->current_password, $admin->password)) {
            return response()->json(['message' => 'A senha atual não confere'], 401);
        }

        $admin->update([
            'password' => Hash::make($request->new_password)
        ]);

        return response()->json(['message' => 'Senha atualizada com sucesso'], 200);
    }
    public function updateAvatar(Request $request){
        $validator = Validator::make($request->all(), [
            'avatar' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $admin = auth()->user();
        $admin->update([
            'avatar' => $request->avatar
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        if($request->hasFile('avatar')){
            if($admin->avatar){
                Storage::disk('public')->delete($admin->avatar);
            }

            $path = $request->file('avatar')->store('admin/avatars', 'public');
            $admin->avatar = $path;
        }

        return response()->json(['message' => 'Avatar atualizado com sucesso', 'img_path' => $admin->avatar], 200);
    }
    public function updateProfile(Request $request){

        $admin = auth()->user();

        $validator = Validator::make($request->all(), [
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
            'bio' => 'nullable|string|max:255|min:10',
            'website' => 'nullable|string',
            'social_facebook' => 'nullable|string',
            'social_twitter' => 'nullable|string',
            'social_instagram' => 'nullable|string',
            'social_linkedin' => 'nullable|string',
        ], [
            'avatar.required' => 'O avatar é obrigatório',
            'avatar.image' => 'O avatar deve ser uma imagem',
            'avatar.mimes' => 'O avatar deve ser um arquivo de imagem com extensão .jpeg, .png, .jpg, .gif, .svg ou .webp',
            'avatar.max' => 'O avatar não pode pesar mais de 2MB',
            'bio.required' => 'A bio é obrigatória',
            'bio.string' => 'A bio deve ser uma string',
            'bio.max' => 'A bio não pode ter mais de 255 caracteres',
            'bio.min' => 'A bio deve ter pelo menos 10 caracteres',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        return response()->json($request->all());

        if($request->hasFile('avatar')){
            if($admin->avatar){
                Storage::disk('public')->delete($admin->avatar);
            }

            $path = $request->file('avatar')->store('admin/avatars', 'public');
            $admin->avatar = $path;
        }

        $admin->update($request->all());

        return response()->json(['message' => 'Perfil atualizado com sucesso', 'img_path' => $admin->avatar], 200);
    }
}
