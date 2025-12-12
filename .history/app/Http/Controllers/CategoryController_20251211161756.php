<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    public function indexPublic(){
        $categories = Category::active()
        ->withCount(['news as published_news_count' => function($query) {
            $query->where('is_published', true);
        }])
        ->orderBy('name')
        ->get();

        return response()->json($categories);
    }

    public function showPublic($slug){
        $category = Category::where('slug', $slug)
        ->active()
        ->firstOrFail();

        return response()->json($category);
    }

    public function index(){
        $categories = Category::withCount('news')
        ->orderBy('name')
        ->paginate(20);

        return response()->json($categories);
    }

    public function store(Request $request){
        $request->validate([
            'name' => 'required|string|max:255|unique:categories,name',
            'description' => 'nullable|string',
            'slug' => 'nullable|string|unique:categories,slug',
            'is_active' => 'boolean',
        ]);

        $category = Category::create([
            'name' => $request->name,
            'slug' => $request->slug ?? Str::slug($request->name),
            'description' => $request->description,
            'is_active' => $request->get('is_active', true)
        ]);

        return response()->json([
            'message' => 'Categoria criada com sucesso',
            'category' => $category
        ], 201);
    }

    public function show($id){
        $category = Category::withCount('news')
        ->findOrFail($id);

        return response()->json($category);
    }

    public function update(Request $request, $id){
        $category = Category::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255|unique:categories,name,' . $id,
            'description' => 'nullable|string',
            'slug' => 'required|string|unique:categories,slug,' . $id,
            'is_active' => 'boolean',
        ]);

        $category->update($request->all());

        return response()->json([
            'message' => 'Categoria atualizada com sucesso',
            'category' => $category
        ]);
    }

    public function destroy($id){
        $category = Category::findOrFail($id);

        if($category->news()->count() > 0) {
            return response()->json([
                'message' => 'Não é possível excluir a categoria pois há notícias vinculadas'
            ], 422);
        }

        $category->delete();

        return response()->json([
            'message' => 'Categoria excluída com sucesso'
        ]);
    }
}
