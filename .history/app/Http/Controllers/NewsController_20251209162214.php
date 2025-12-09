<?php

namespace App\Http\Controllers;

use App\Models\Admin;
use App\Models\Category;
use Illuminate\Http\Request;
use App\Models\News;
use App\Models\Tag;

class NewsController extends Controller
{
    public function indexPublic(Request $request){
        $query = News::with(['category', 'author', 'tags'])
        ->published()
        ->orderBy('published_at', 'desc');

        if($request->has('category')){
            $category = Category::where('slug', $request->category)->first();
            if($category){
                $query->where('category_id', $category->id);
            }
        }

        if($request->has('author')){
            $author = Admin::where('slug', $request->author)->first();
            if($author){
                $query->where('author_id', $author->id);
            }
        }

        if($request->has('tag')){
            $tag = Tag::where('slug', $request->tag)->first();
            if($tag){
                $query->whereHas('tags', function($q) use ($tag){
                    $q->where('tags.id', $tag->id);
                });
            }
        }
        $perPage = $request->get('per_page', 10);
        $news = $query->paginate($perPage);
        return response()->json($news);
    }
    public function featured(){
        $news = News::with(['category', 'author'])
        ->featured()
        ->orderBy('published_at', 'desc')
        ->limit(5)
        ->get();

        return response()->json($news);
    }
    public function recent(){
        $news = News::with(['category', 'author'])
        ->published()
        ->recent(30)
        ->orderBy('published_at', 'desc')
        ->limit(10)
        ->get();

        return response()->json($news);
    }
    public function showPublic($id){
        $news = News::with(['category', 'author', 'tags', 'gallery'])
        ->published()
        ->findOrFail($id);

        return response()->json($news);
    }
    public function showBySlug($slug){
        $news = News::with(['category', 'author', 'tags', 'gallery'])
        ->published()
        ->where('slug', $slug)
        ->firstOrFail();

        return response()->json($news);
    }
    public function incrementView($id){
        $news = News::findOrFail($id);
        $news->incrementViews();

        return response()->json(['message' => 'View incremented successfully', 'views_count' => $news->views_count]);
    }
}
