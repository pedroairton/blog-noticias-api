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
        ]);

        $tag = Tag::create([
            'name' => $request->name,
            'slug' => $request->slug ?? Str::slug($request->name),
        ]);

        return response()->json([
            'message' => 'Tag criada com sucesso',
            'tag' => $tag
        ], 201);
    }

    public function update(Request $request, $id) {
        $tag = Tag::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:32|unique:tags,name,' . $id,
            'slug' => 'nullable|string|unique:tags,slug,' . $id,
        ]);

        $tag->slug = $request->slug ?? Str::slug($request->name);
        $tag->update($request->all());

        return response()->json([
            'message' => 'Tag atualizada com sucesso',
            'tag' => $tag
        ]);
    }
    public function destroy($id) {
        $tag = Tag::findOrFail($id);
        $tag->delete();
        return response()->json([
            'message' => 'Tag excluída com sucesso',
            'tag' => $tag
        ]);
    }
}
