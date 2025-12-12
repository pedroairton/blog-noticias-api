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
}
