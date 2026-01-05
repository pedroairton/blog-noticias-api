<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Tag;
use Illuminate\Support\Str;

class TagController extends Controller
{
    public function index()
    {
        $tags = Tag::withCount('news')
        ->where('is_active', true)
        ->orderBy('name')
        ->paginate(20);

        return view('admin.tags.index', compact('tags'));
    }
    public function store(Request $request){
        $request->validate([
            'name' => 'required|string|max:32|unique:tags,name',
            'slug' => 'nullable|string|unique:tags,slug',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $tag = Tag::create([
            'name' => $request->name,
            'slug' => $request->slug ?? Str::slug($request->name),
            'description' => $request->description,
            'is_active' => $request->get('is_active', true)
        ]);

        return response()->json([
            'message' => 'Tag criada com sucesso',
            'tag' => $tag
        ], 201);
    }
}
