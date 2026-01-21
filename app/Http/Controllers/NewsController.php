<?php

namespace App\Http\Controllers;

use App\Models\Admin;
use App\Models\Category;
use Illuminate\Http\Request;
use App\Models\News;
use App\Models\Tag;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class NewsController extends Controller
{
    public function indexPublic(Request $request)
    {
        $query = News::with(['category', 'author', 'tags'])
            ->published()
            ->orderBy('published_at', 'desc');

        if ($request->has('category')) {
            $category = Category::where('slug', $request->category)->first();
            if ($category) {
                $query->where('category_id', $category->id);
            }
        }

        if ($request->has('author')) {
            $author = Admin::where('slug', $request->author)->first();
            if ($author) {
                $query->where('author_id', $author->id);
            }
        }

        if ($request->has('tag')) {
            $tag = Tag::where('slug', $request->tag)->first();
            if ($tag) {
                $query->whereHas('tags', function ($q) use ($tag) {
                    $q->where('tags.id', $tag->id);
                });
            }
        }

        $perPage = $request->get('per_page', 10);
        $news = $query->paginate($perPage);
        return response()->json($news);
    }
    public function featured()
    {
        $news = News::with(['category', 'author'])
            ->featured()
            ->orderBy('published_at', 'desc')
            ->limit(5)
            ->get();

        return response()->json($news);
    }
    public function recent()
    {
        $news = News::with(['category', 'author'])
            ->published()
            ->orderBy('published_at', 'desc')
            ->limit(4)
            ->select('title', 'subtitle', 'slug', 'main_image', 'published_at', 'category_id', 'content')
            ->get();

        return response()->json($news);
    }
    public function showPublic($id)
    {
        $news = News::with(['category', 'author', 'tags', 'gallery'])
            ->published()
            ->findOrFail($id);

        return response()->json($news);
    }
    public function showBySlug($slug)
    {
        $news = News::with(['category', 'author', 'tags', 'gallery'])
            ->published()
            ->where('slug', $slug)
            ->firstOrFail();

        return response()->json($news);
    }
    public function incrementView($id)
    {
        $news = News::findOrFail($id);
        $news->incrementViews();

        return response()->json(['message' => 'Visualização registrada', 'views_count' => $news->views_count]);
    }
    public function searchPublic(Request $request)
    {
        $request->validate([
            'q' => 'required|string|min:3'
        ]);

        $news = News::with(['category', 'author'])
            ->published()
            ->search($request->q)
            ->orderBy('published_at', 'desc')
            ->paginate(10);

        return response()->json($news);
    }
    public function randomCategory(){
        $category = Category::inRandomOrder()->with(['news' => function($query){
            $query->published()->orderBy('published_at', 'desc')->limit(3);
        }])->take(4)->get();
        return response()->json($category);
    }
    public function byCategory($categorySlug)
    {
        $category = Category::where('slug', $categorySlug)->firstOrFail();

        $news = News::with(['category', 'author', 'tags'])
            ->published()
            ->where('category_id', $category->id)
            ->orderBy('published_at', 'desc')
            ->paginate(10);

        return response()->json(['category' => $category, 'news' => $news]);
    }
    public function byAuthor($authorSlug)
    {
        $author = Admin::where('slug', $authorSlug)
            ->where('role', 'author')
            ->firstOrFail();

        $news = News::with(['category', 'tags'])
            ->published()
            ->where('author_id', $author->id)
            ->orderBy('published', 'desc')
            ->paginate(10);

        return response()->json(['author' => $author, 'news' => $news]);
    }
    public function byTag($tagSlug)
    {
        $tag = Tag::where('slug', $tagSlug)->firstOrFail();

        $news = $tag->news()
            ->with(['category', 'author'])
            ->published()
            ->orderBy('published_at', 'desc')
            ->paginate(10);

        return response()->json(['news' => $news, 'tag' => $tag]);
    }
    public function mostViewed(){
        dd('test');
        $news = News::with(['category'])
            ->mostViewed()
            ->get();
        return response()->json($news);
    }

    public function index(Request $request)
    {
        $user = auth()->user();
        $query = News::with(['category', 'author', 'tags'])
            ->orderBy('created_at', 'desc');

        if ($user) {
            $query->where('author_id', $user->id);
        }

        if ($request->has('status')) {
            if ($request->status === 'published') {
                $query->where('is_published', true);
            } elseif ($request->status === 'draft') {
                $query->where('is_published', false);
            }
        }
        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->has('search')) {
            $query->search($request->search);
        }

        $perPage = $request->get('per_page', 15);
        $news = $query->paginate($perPage);

        return response()->json($news);
    }
    public function store(Request $request)
    {
        $user = auth()->user();

        if ($request->has('is_published')) {
            $request->merge([
                'is_published' => filter_var($request->is_published, FILTER_VALIDATE_BOOLEAN)
            ]);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'subtitle' => 'nullable|string|max:500',
            'slug' => 'nullable|string|unique:news,slug',
            'excerpt' => 'required|string|max:500',
            'content' => 'required|string',
            'category_id' => 'required|exists:categories,id',
            'main_image' => 'nullable|image|max:2048',
            'main_image_caption' => 'nullable|string|max:255',
            'main_image_alt' => 'nullable|string|max:255',
            'tags' => 'nullable|array',
            'tags.*' => 'exists:tags,id',
            'is_published' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors(),
                $request->all()
            ], 422);
        }

        $data = $request->only([
            'title',
            'subtitle',
            'slug',
            'excerpt',
            'content',
            'category_id',
            'main_image_caption',
            'main_image_alt'
        ]);

        $data['author_id'] = $user->id;

        if ($request->has('is_published')) {
            if ($user->isSuperAdmin()) {
                $data['is_published'] = $request->is_published;
                if ($request->is_published) {
                    $data['published_at'] = now();
                }
            } else {
                $data['is_published'] = false;
            }
        }

        if ($request->hasFile('main_image')) {
            $path = $request->file('main_image')->store('news/main', 'public');
            $data['main_image'] = $path;
        }

        $news = News::create($data);

        if ($news->gallery()->count() > 0) {
            $processedContent = $this->processContentImages($news, $news->content);
            $news->update(['content' => $processedContent]);
        }

        if ($request->has('tags')) {
            $news->tags()->sync($request->tags);
        }

        // Se houver imagens da galeria, processar placeholders
        if ($request->has('gallery_processed')) {
            $this->processContentPlaceholders($news, $request->content);
        }

        return response()->json([
            'message' => 'Notícia criada com sucesso',
            'news' => $news->load(['category', 'author', 'tags'])
        ], 201);
    }
    public function show($id)
    {
        $news = News::with(['category', 'author', 'tags', 'gallery'])->findOrFail($id);

        if (!auth()->user()->canModifyNews($news)) {
            return response()->json([
                'message' => 'Você não tem permissão para visualizar esta notícia'
            ], 403);
        }
        return response()->json($news);
    }
    public function update(Request $request, $id)
    {
        \Log::info('Dados recebidos:', $request->all());
        \Log::info('Headers recebidos:', $request->headers->all());
        // return response()->json($request);
        $news = News::findOrFail($id);
        $user = auth()->user();

        if (!$user->canModifyNews($news)) {
            return response()->json([
                'message' => 'Você não tem permissão para editar esta notícia'
            ], 403);
        }

        if ($request->has('is_published')) {
            $request->merge([
                'is_published' => filter_var($request->is_published, FILTER_VALIDATE_BOOLEAN)
            ]);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|required|string|max:255',
            'subtitle' => 'nullable|string|max:500',
            'slug' => 'sometimes|string|unique:news,slug,' . $id,
            'excerpt' => 'sometimes|required|string|max:500',
            'content' => 'sometimes|required|string',
            'category_id' => 'sometimes|required|exists:categories,id',
            'main_image' => 'nullable|image|max:2048',
            'main_image_caption' => 'nullable|string|max:255',
            'main_image_alt' => 'nullable|string|max:255',
            'tags' => 'nullable|array',
            'tags.*' => 'exists:tags,id',
            'is_published' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $request->only([
            'title',
            'subtitle',
            'slug',
            'excerpt',
            'content',
            'category_id',
            'main_image_caption',
            'main_image_alt'
        ]);

        // Publicação: apenas superadmins podem publicar
        if ($request->has('is_published')) {
            if ($user->isSuperAdmin()) {
                $data['is_published'] = $request->is_published;
                if ($request->is_published && !$news->published_at) {
                    $data['published_at'] = now();
                }
            }
        }

        // Upload da imagem principal
        if ($request->hasFile('main_image')) {
            // Remover imagem antiga se existir
            if ($news->main_image) {
                Storage::disk('public')->delete($news->main_image);
            }

            $path = $request->file('main_image')->store('news/main', 'public');
            $data['main_image'] = $path;
        }

        // Atualizar notícia
        $news->update($data);

        // Sincronizar tags se fornecidas
        if ($request->has('tags')) {
            $news->tags()->sync($request->tags);
        }

        if ($news->gallery()->count() > 0) {
            $processedContent = $this->processContentImages($news, $news->content);
            $news->update(['content' => $processedContent]);
        }

        if ($request->has('gallery_processed')) {
            $this->processContentPlaceholders($news, $request->content);
        }

        return response()->json([
            'message' => 'Notícia atualizada com sucesso',
            'news' => $news->fresh(['category', 'author', 'tags'])
        ]);
    }
    public function destroy($id)
    {
        $news = News::findOrFail($id);

        if (!auth()->user()->canModifyNews($news)) {
            return response()->json([
                'message' => 'Você não tem permissão para excluir esta notícia'
            ], 403);
        }

        $news->delete();

        return response()->json([
            'message' => 'Notícia excluída com sucesso'
        ], 200);
    }
    public function myNews(Request $request)
    {
        $user = auth()->user();
        $news = $user->news()
            ->with(['category', 'tags'])
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return response()->json($news);
    }
    public function drafts()
    {
        $user = auth()->user();

        $query = News::where('is_published', false);

        if ($user->isAuthor()) {
            $query->where('author_id', $user->id);
        }

        $drafts = $query->with(['category', 'author'])
            ->orderBy('updated_at', 'desc')
            ->paginate(15);

        return response()->json($drafts);
    }
    public function scheduled(){
        $user = auth()->user();
        
        $scheduled = News::where('is_published', false)
            ->where('published_at', '>', now())
            ->where('author_id', $user->id)
            ->with(['category', 'author'])
            ->orderBy('published_at', 'asc')
            ->paginate(15);
        return response()->json($scheduled);
    }
    public function publish($id)
    {
        $news = News::findOrFail($id);

        if (!auth()->user()->isSuperAdmin()) {
            return response()->json([
                'message' => 'Apenas superadministradores podem publicar notícias'
            ], 403);
        }

        $news->publish();

        return response()->json([
            'message' => 'Notícia publicada com sucesso',
            'news' => $news
        ]);
    }
    public function unpublish($id)
    {
        $news = News::findOrFail($id);

        if (!auth()->user()->isSuperAdmin()) {
            return response()->json([
                'message' => 'Apenas superadministradores podem despublicar notícias'
            ], 403);
        }

        $news->unpublish();

        return response()->json([
            'message' => 'Notícia despublicada com sucesso',
            'news' => $news
        ]);
    }
    public function syncTags(Request $request, $id)
    {
        $news = News::findOrFail($id);

        if (!auth()->user()->canModifyNews($news)) {
            return response()->json([
                'message' => 'Você não tem permissão para modificar esta notícia'
            ], 403);
        }

        $request->validate([
            'tags' => 'required|array',
            'tags.*' => 'exists:tags,id'
        ]);

        $news->tags()->sync($request->tags);

        return response()->json([
            'message' => 'Tags atualizadas com sucesso',
            'tags' => $news->tags
        ]);
    }
    public function dashboardStats()
    {
        $user = auth()->user();
        $stats = [];

        if ($user->isSuperAdmin()) {
            $stats = [
                'total_news' => News::count(),
                'published_news' => News::where('is_published', true)->count(),
                'draft_news' => News::where('is_published', false)->count(),
                'total_authors' => Admin::authors()->count(),
                'total_categories' => Category::count(),
                'total_tags' => Tag::count(),
                'total_views' => News::sum('view_count'),
                'recent_news' => News::published()
                    ->orderBy('published_at', 'desc')
                    ->limit(5)
                    ->get(['id', 'title', 'published_at', 'view_count'])
            ];
        } else {
            $stats = [
                'my_total_news' => $user->news()->count(),
                'my_published_news' => $user->news()->where('is_published', true)->count(),
                'my_draft_news' => $user->news()->where('is_published', false)->count(),
                'my_total_views' => $user->news()->sum('view_count'),
                'recent_my_news' => $user->news()
                    ->orderBy('created_at', 'desc')
                    ->limit(5)
                    ->get(['id', 'title', 'created_at', 'view_count'])
            ];
        }
        return response()->json($stats);
    }
    private function processContentPlaceholders(News $news, string $content): void
    {
        // Extrair placeholders do conteúdo
        preg_match_all('/{{(IMAGE_PLACEHOLDER_|EXISTING_IMAGE_)(\d+)}}/', $content, $matches);

        $placeholders = $matches[0] ?? [];

        foreach ($placeholders as $placeholder) {
            // Determinar se é uma imagem existente ou nova
            preg_match('/{{(IMAGE_PLACEHOLDER_|EXISTING_IMAGE_)(\d+)}}/', $placeholder, $match);
            $type = $match[1];
            $index = $match[2];

            if ($type === 'EXISTING_IMAGE_') {
                // Imagem existente - buscar da galeria
                $galleryImage = $news->gallery()->find($index);
                if ($galleryImage) {
                    $imageUrl = asset('storage/' . $galleryImage->image_path);
                    $content = str_replace($placeholder, $imageUrl, $content);
                }
            } elseif ($type === 'IMAGE_PLACEHOLDER_') {
                // Nova imagem - será processada depois do upload
                // Manter placeholder por enquanto
                continue;
            }
        }

        // Atualizar conteúdo apenas se houve substituições
        if ($placeholders) {
            $news->update(['content' => $content]);
        }
    }
    private function processContentImages(News $news, string $content): string
    {
        preg_match_all('/{{IMAGE_PLACEHOLDER_(\d+)}}/', $content, $matches);

        $placeholders = $matches[0] ?? [];

        if (empty($placeholders)) {
            return $content;
        }

        $galleryImages = $news->gallery()->get();

        foreach ($placeholders as $placeholder) {
            preg_match('/{{IMAGE_PLACEHOLDER_(\d+)}}/', $placeholder, $match);
            $index = $match[1] ?? null;

            if ($index !== null && isset($galleryImages[$index])) {
                $image = $galleryImages[$index];
                $imageUrl = asset('storage/' . $image->image_path);
                $content = str_replace($placeholder, $imageUrl, $content);
            }
        }

        return $content;
    }
}
