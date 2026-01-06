<?php

namespace App\Http\Controllers;

use App\Models\News;
use App\Models\NewsGallery;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class NewsGalleryController extends Controller
{
    public function store(Request $request, $newsId){
        $news = News::findOrFail($newsId);

        if(!auth()->user()->canModifyNews($news)) {
            return response()->json([
                'message' => 'Você não tem permissão para adicionar imagens a esta notícia'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'images.*' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'captions.*' => 'nullable|string|max:255',
            'alt_texts.*' => 'nullable|string|max:255',
        ]);

        if($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }

        $uploadedImages = [];

        $lastPosition = $news->gallery()->max('position') ?? 0;
        $position = $lastPosition+1;

        if($request->hasFile('images')) {
            foreach($request->file('images') as $index => $image) {
                try{
                    $fileName = Str::uuid() . '.' . $image->getClientOriginalExtension();
                    $filePath = 'news/gallery/' . $fileName;

                    Storage::disk('public')->put($filePath, file_get_contents($image));

                    $dimensions = getimagesize($image);

                    $galleryImage = NewsGallery::create([
                        'news_id' => $news->id,
                        'image_path' => $filePath,
                        'caption' => $request->captions[$index] ?? null,
                        'alt_text' => $request->alt_texts[$index] ?? null,
                        'position' => $position++,
                        'original_name' => $image->getClientOriginalName(),
                        'mime_type' => $image->getMimeType(),
                        'file_size' => $image->getSize(),
                        'dimensions' => [
                            'width' => $dimensions[0] ?? null,
                            'height' => $dimensions[1] ?? null
                        ],
                    ]);

                    $galleryImage->image_url = asset('storage/' . $filePath);
                    $uploadedImages[] = $galleryImage;
                } catch (\Exception $e) {
                    Log::error('Erro ao processar imagem da galeria: '. $e->getMessage());
                    continue;
                }
            }
        }

        return response()->json([
            'message' => count($uploadedImages) . ' imagens adicionadas com sucesso',
            'images' => $uploadedImages
        ], 201);
    }
    public function replacePlaceholders(Request $request, $newsId){
        $news = News::findOrFail($newsId);

        if(!auth()->user()->canModifyNews($news)){
            return response()->json([
                'message' => 'Você não tem permissão para modificar esta notícia'
            ], 403);
        }

        $request->validate([
            'content' => 'required|string',
            'placeholders' => 'required|array',
            'placeholders.*.placeholder' => 'required|string',
            'placeholders.*.image_path' => 'required|string'
        ]);

        $content = $request->content;

        foreach ($request->placeholders as $placeholderData) {
            $imageUrl = asset('storage/' . $placeholderData['image_path']);
            $content = str_replace(
                $placeholderData['placeholder'],
                $imageUrl,
                $content
            );
        }

        $news->update(['content' => $content]);

        return response()->json([
            'message' => 'Placeholders substituídos com sucesso',
            'content' => $content
        ], 200);
    }
}
