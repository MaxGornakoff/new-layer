<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\HeroSlideResource;
use App\Models\HeroSlide;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class HeroSlideController extends Controller
{
    public function index(): JsonResponse
    {
        $slides = HeroSlide::query()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return HeroSlideResource::collection($slides)->response();
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validatedData($request, requireImage: true);

        $slide = HeroSlide::create([
            ...$data,
            'image' => $request->file('image')->store('hero-slides', 'public'),
        ]);

        return (new HeroSlideResource($slide))
            ->response()
            ->setStatusCode(201);
    }

    public function update(Request $request, HeroSlide $heroSlide): JsonResponse
    {
        $data = $this->validatedData($request);

        if ($request->hasFile('image')) {
            $this->deleteFile($heroSlide->image);
            $heroSlide->image = $request->file('image')->store('hero-slides', 'public');
        }

        $heroSlide->fill($data);
        $heroSlide->save();

        return (new HeroSlideResource($heroSlide->fresh()))->response();
    }

    public function destroy(HeroSlide $heroSlide): JsonResponse
    {
        $this->deleteFile($heroSlide->image);
        $heroSlide->delete();

        return response()->json(['message' => 'Deleted']);
    }

    private function validatedData(Request $request, bool $requireImage = false): array
    {
        $request->validate([
            'image' => [$requireImage ? 'required' : 'nullable', 'file', 'mimes:jpeg,jpg,png,webp', 'max:4096'],
            'title' => ['required', 'string', 'max:2000'],
            'subtitle' => ['nullable', 'string', 'max:5000'],
            'button_text' => ['nullable', 'string', 'max:100'],
            'button_url' => ['nullable', 'string', 'max:2048'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        return [
            'title' => $request->string('title')->toString(),
            'subtitle' => $request->input('subtitle'),
            'button_text' => $request->input('button_text'),
            'button_url' => $request->input('button_url'),
            'sort_order' => $request->integer('sort_order', 0),
            'is_active' => $request->boolean('is_active', true),
        ];
    }

    private function deleteFile(?string $path): void
    {
        if ($path) {
            Storage::disk('public')->delete($path);
        }
    }
}
