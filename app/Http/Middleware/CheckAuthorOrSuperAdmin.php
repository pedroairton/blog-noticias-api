<?php

namespace App\Http\Middleware;

use App\Models\News;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckAuthorOrSuperAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if(!$user) {
            return response()->json([
                'message' => 'Usuário não autenticado.'
            ], 401);
        }

        if($user->isSuperAdmin()) {
            return $next($request);
        }

        if($request->route('news') || $request->route('id')) {
            $newsId = $request->route('news') ?? $request->route('id');
            $news = News::find($newsId);

            if(!$news) {
                return response()->json([
                    'message' => 'Notícia não encontrada'
                ], 404);
            }

            if($news->author_id !== $user->id) {
                return response()->json([
                    'message' => 'Você não tem permissão para modificar esta notícia.'
                ], 403);
            }
        }

        return $next($request);
    }
}
