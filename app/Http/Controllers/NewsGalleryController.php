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
    public function index($newsId){
        $news = News::findOrFail($newsId);

        if(!auth()->user()->canModifyNews($news)) {
            return response()->json([
                'message' => 'Você não tem permissão para visualizar a galeria desta notícia'
            ], 403);
        }

        $gallery = $news->gallery()->orderBy('position', 'asc')->get();

        return response()->json($gallery);
    }
    public function store(Request $request, $newsId){
        $news = News::findOrFail($newsId);

        if(!auth()->user()->canModifyNews($news)) {
            return response()->json([
                'message' => 'Você não tem permissão para adicionar imagens a esta notícia'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'images.*' => 'required|image|mimes:jpeg,png,jpg,gif,webp,avif|max:2048',
            'captions.*' => 'nullable|string|max:255',
            'alt_texts.*' => 'nullable|string|max:255',
            'existing_image_ids' => 'nullable|string',
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

        $existingImages = [];
        if($request->has('existing_image_ids')) {
            $existingImageIds = json_decode($request->existing_image_ids, true);
            if(is_array($existingImageIds)){
                $existingImages = NewsGallery::whereIn('id', $existingImageIds)
                ->where('news_id', $newsId)
                ->get();
            }
        }

        return response()->json([
            'message' => count($uploadedImages) . ' imagens adicionadas com sucesso',
            'images' => $uploadedImages,
            'existing_images' => $existingImages
        ], 201);
    }
    public function storeSingle(Request $request, $newsId){
        $news = News::findOrFail($newsId);

        if(!auth()->user()->canModifyNews($news)){
            return response()->json([
                'message' => 'Você não tem permissão para adicionar imagens à esta notícia',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,webp,avif|max:2048',
            'caption' => 'nullable|string|max:255',
            'alt_text' => 'nullable|string|max:255',
            'position' => 'nullable|integer',
        ]);

        if($validator->fails()){
            return response()->json([
                'message' => 'Erro ao processar imagem da galeria',
                'errors' => $validator->errors()
            ], 422);
        }

        $image = $request->file('image');

        $fileName = Str::uuid() . '.' . $image->getClientOriginalExtension();
        $filePath = 'news/gallery/' . $fileName;

        Storage::disk('public')->put($filePath, file_get_contents($image));

        $thumbnailPath = $this->createThumbnail($image, $fileName);

        $position = $request->position ?? ($news->gallery()->max('position') ?? 0) + 1;

        $galleryImage = NewsGallery::create([
            'news_id' => $news->id,
            'image_path' => $filePath,
            'thumbnail_path' => $thumbnailPath,
            'caption' => $request->caption,
            'alt_text' => $request->alt_text,
            'position' => $position,
            'original_name' => $image->getClientOriginalName(),
            'mime_type' => $image->getMimeType(),
            'file_size' => $image->getSize(),
            'dimensions' => [
                'width' => getimagesize($image)[0] ?? null,
                'height' => getimagesize($image)[1] ?? null,
            ],
        ]);

        return response()->json([
            'message' => 'Imagem adicionada com sucesso',
            'galleryImage' => $galleryImage
        ], 201);
    }
    public function update(Request $request, $newsId, $id){
        $galleryImage = NewsGallery::where('news_id', $newsId)->findOrFail($id);

        if(!auth()->user()->canModifyNews($galleryImage->news)){
            return response()->json([
                'message' => 'Você não tem permissão para atualizar esta imagem da galeria',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'caption' => 'nullable|string|max:255',
            'alt_text' => 'nullable|string|max:255',
            'position' => 'nullable|integer',
        ]);

        if ($validator->fails()){
            return response()->json([
                'message' => 'Erro ao atualizar imagem da galeria',
                'errors' => $validator->errors()
            ], 422);
        }

        $galleryImage->update([
            'caption' => $request->caption,
            'alt_text' => $request->alt_text,
            'position' => $request->position ?? $galleryImage->position,
        ]);

        return response()->json([
            'message' => 'Imagem da galeria atualizada com sucesso',
            'galleryImage' => $galleryImage
        ], 200);
    }
    public function reorder(Request $request, $newsId){
        $news = News::findOrFail($newsId);

        if(!auth()->user()->canModifyNews($news)){
            return response()->json([
                'message' => 'Você não tem permissão para reordenar as imagens da galeria',
            ], 403);
        }

        $request->validate([
            'images' => 'required|array',
            'images.*.id' => 'required|exists:news_galleries,id',
            'images.*.position' => 'required|integer',
        ]);

        foreach($request->images as $imageData) {
            NewsGallery::where('id', $imageData['id'])
            ->where('news_id', $newsId)
            ->update(['position' => $imageData['position']]);
        }

        return response()->json([
            'message' => 'Imagens da galeria reordenadas com sucesso'
        ], 200);
    }
    public function destroy($newsId, $id) {
        $galleryImage = NewsGallery::where('news_id', $newsId)->findOrFail($id);

        if(!auth()->user()->canModifyNews($galleryImage->news)){
            return response()->json([
                'message' => 'Você não tem permissão para excluir esta imagem da galeria',
            ], 403);
        }

        if($galleryImage->image_path) {
            Storage::disk('public')->delete($galleryImage->image_path);
        }

        if($galleryImage->thumbnail_path) {
            Storage::disk('public')->delete($galleryImage->thumbnail_path);
        }

        $galleryImage->delete();

        return response()->json([
            'message' => 'Imagem da galeria excluída com sucesso'
        ], 200);
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
    private function createThumbnail($image, $fileName) {
        try{
            // Usar intervention/image se instalado
            // $img = Image::make($image)->resize(300, 200)->save();

            $thumbnailPath = 'news/gallery/thumbnails/' . $fileName;

            // Copiar a imagem original como thumbnail (simplificado)
            Storage::disk('public')->put($thumbnailPath, file_get_contents($image));

            return $thumbnailPath;
        } catch (\Exception $e) {
            return null;
        }
    }
}
