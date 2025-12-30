<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\News;
use App\Models\NewsGallery;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class NewsGalleryController extends Controller
{
    //
    public function index($newsId){
        $news = News::findOrFail($newsId);

        $this->authorize('view', $news);

        $gallery = $news->gallery()->orderBy('position', 'asc')->get();

        return response()->json($gallery);
    }

    public function store(Request $request, $newsId)
    {
        $news = News::findOrFail($newsId);

        $this->authorize('update', $news);

        $validator = Validator::make($request->all(), [
            'images.*' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'captions.*' => 'nullable|string|max:255',
            'alt_texts.*' => 'nullable|string|max:255'
        ]);

        if ($validator->fails()) {
            return response()->json([
                $validator->errors()
            ], 422);
        }

        $uploadedImages = [];

        $lastPosition = $news->gallery()->max('position') ?? 0;
        $position = $lastPosition+1;

        if($request->hasFile('images')) {
            foreach($request->file('images') as $index => $image) {
                $fileName = Str::uuid() . '.' . $image->getClientOriginalExtension();
                $filePath = 'news/gallery/'.$fileName;

                Storage::disk('public')->put($filePath, file_get_contents($image));

                $thumbnailPath = $this->createThumbnail($image,$fileName);

                $galleryImage = NewsGallery::create([
                    'news_id' => $news->id,
                    'image_path' => $filePath,
                    'thumbnail_path' => $thumbnailPath,
                    'caption' => $request->input('captions.' . $index),
                    'alt_text' => $request->input('alt_texts.' . $index),
                    'position' => $position++,
                    'original_name' => $image->getClientOriginalName(),
                    'mime_type' => $image->getMimeType(),
                    'file_size' => $image->getSize(),
                    'dimensions' => [
                        'width' => getimagesize($image)[0] ?? null,
                        'height' => getimagesize($image)[1] ?? null
                    ]
                ]);

                $uploadedImages[] = $galleryImage;
            }
        }

        return response()->json([
            'message' => 'Imagens enviadas com sucesso',
            'images' => $uploadedImages
        ], 201);
    }

    public function storeSingle(Request $request, $newsId) {
        $news = News::findOrFail($newsId);

        $this->authorize('update', $news);

        $validator = Validator::make($request->all(), [
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'caption' => 'nullable|string|max:255',
            'alt_text' => 'nullable|string|max:255',
            'position' => 'nullable|integer'
        ]);

        if($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }

        $image = $request->file('image');

        $fileName = Str::uuid() . '.' . $image->getClientOriginalExtension();
        $filePath = 'news/gallery/'. $fileName;

        Storage::disk('public')->put($filePath, file_get_contents($image));

        $thumbnailPath = $this->createThumbnail($image, $fileName);

        $position = $request->position ?? ($news->gallery()->max('position') ?? 0) + 1;

        $galleryImage = NewsGallery::create([
            'news_id' => $newsId,
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
                'height' => getimagesize($image)[1] ?? null
            ]
        ]);
    }

    public function update(Request $request, $newsId, $id){
        $galleryImage = NewsGallery::where('news_id', $newsId)->where('id', $id)->first();

        $this->authorize('update', $galleryImage->news);

        $validator = Validator::make($request->all(), [
            'caption' => 'nullable|string|max:255',
            'alt_text' => 'nullable|string|max:255',
            'position' => 'nullable|integer'
        ]);

        if($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }

        $galleryImage->update([
            'caption' => $request->caption,
            'alt_text' => $request->alt_text,
            'position' => $request->position ?? $galleryImage->position
        ]);

        return response()->json([
            'message' => 'Imagem atualizada com sucesso',
            'image' => $galleryImage
        ], 200);
    }
}
