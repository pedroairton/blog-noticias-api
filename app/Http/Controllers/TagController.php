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
        ->orderBy('name')
        ->paginate(20);

        return response()->json($tags);
    }
    public function store(Request $request){
        $request->validate([
            'name' => 'required|string|max:32|unique:tags,name',
            'slug' => 'nullable|string|unique:tags,slug',
            'description' => 'nullable|string',
        ]);

        $tag = Tag::create([
            'name' => $request->name,
            'slug' => $request->slug ?? Str::slug($request->name),
            'description' => $request->description,
        ]);

        return response()->json([
            'message' => 'Tag criada com sucesso',
            'tag' => $tag
        ], 201);
    }
}
