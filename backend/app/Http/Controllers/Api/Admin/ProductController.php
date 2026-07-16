<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $products = Product::query()
            ->with('category:id,name,slug')
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search')->toString();

                $query->where(function ($builder) use ($search) {
                    $builder
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('sku', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(20);

        return ProductResource::collection($products)->response();
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validatedData($request);
        $data['slug'] = $this->uniqueSlug($data['name']);

        if ($request->hasFile('video')) {
            $data['video'] = $request->file('video')->store('products/videos', 'public');
        }

        $imagePaths = [];

        if ($request->hasFile('image')) {
            $imagePaths[] = $request->file('image')->store('products/images', 'public');
        }

        foreach ($this->uploadedImageFiles($request) as $file) {
            $imagePaths[] = $file->store('products/images', 'public');
        }

        $imagePaths = array_values(array_filter($imagePaths));

        if ($imagePaths !== []) {
            $data['images'] = $imagePaths;
            // legacy compatibility (single image field)
            $data['image'] = $imagePaths[0];
        }

        $product = Product::create($data);
        $product->load('category:id,name,slug');

        return (new ProductResource($product))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Product $product): JsonResponse
    {
        $product->load('category:id,name,slug');

        return (new ProductResource($product))->response();
    }

    public function update(Request $request, Product $product): JsonResponse
    {
        $data = $this->validatedData($request, $product);

        if ($data['name'] !== $product->name) {
            $data['slug'] = $this->uniqueSlug($data['name'], $product->id);
        }

        $existingImages = is_array($product->images) ? $product->images : [];
        $existingImages = array_values(array_filter($existingImages, fn ($p) => is_string($p) && trim($p) !== ''));
        if ($existingImages === [] && filled($product->image)) {
            $existingImages = [$product->image];
        }

        if ($request->boolean('remove_image') && filled($product->image)) {
            $this->deleteFile($product->image);
            $existingImages = array_values(array_filter($existingImages, fn ($p) => $p !== $product->image));
            $data['image'] = null;
        }

        $removeImages = $request->input('remove_images', []);
        if (is_array($removeImages) && $removeImages !== []) {
            foreach ($removeImages as $path) {
                if (is_string($path) && trim($path) !== '') {
                    $this->deleteFile($path);
                }
            }

            $existingImages = array_values(array_filter(
                $existingImages,
                fn ($p) => !in_array($p, $removeImages, true)
            ));
        }

        $imageOrder = $request->input('image_order', []);
        if (is_array($imageOrder) && $imageOrder !== []) {
            $existingImages = collect($imageOrder)
                ->filter(fn ($path) => is_string($path) && in_array($path, $existingImages, true))
                ->values()
                ->all();
        }

        $storedNewImages = [];
        foreach ($this->uploadedImageFiles($request) as $file) {
            $storedNewImages[] = $file->store('products/images', 'public');
        }

        $gallerySequence = $request->input('gallery_sequence', []);
        if (is_array($gallerySequence) && $gallerySequence !== []) {
            $existingImages = $this->mergeGallerySequence(
                $gallerySequence,
                $existingImages,
                $storedNewImages
            );
        } elseif ($storedNewImages !== []) {
            foreach ($storedNewImages as $path) {
                $existingImages[] = $path;
            }
        }

        if ($request->boolean('remove_video')) {
            $this->deleteFile($product->video);
            $data['video'] = null;
        }

        if ($request->hasFile('video')) {
            $this->deleteFile($product->video);
            $data['video'] = $request->file('video')->store('products/videos', 'public');
        }

        // legacy single image replacement (main image)
        if ($request->hasFile('image')) {
            if (filled($product->image)) {
                $this->deleteFile($product->image);
                $existingImages = array_values(array_filter($existingImages, fn ($p) => $p !== $product->image));
            }

            $mainPath = $request->file('image')->store('products/images', 'public');
            array_unshift($existingImages, $mainPath);
            $data['image'] = $mainPath;
        }

        $existingImages = array_values(array_filter($existingImages, fn ($p) => is_string($p) && trim($p) !== ''));

        $data['images'] = $existingImages;
        $data['image'] = $existingImages[0] ?? null;

        $product->update($data);
        $product->load('category:id,name,slug');

        return (new ProductResource($product))->response();
    }

    public function destroy(Product $product): JsonResponse
    {
        if ($product->orderItems()->exists()) {
            return response()->json([
                'message' => 'Нельзя удалить товар, который уже есть в заказах.',
            ], 422);
        }

        $galleryImages = is_array($product->images) ? $product->images : [];
        foreach ($galleryImages as $path) {
            $this->deleteFile($path);
        }

        $this->deleteFile($product->image);
        $this->deleteFile($product->video);
        $product->delete();

        return response()->json(['message' => 'Deleted']);
    }

    private function validatedData(Request $request, ?Product $product = null): array
    {
        $request->validate([
            'category_id' => ['required', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:255'],
            'sku' => [
                'required',
                'string',
                'max:64',
                Rule::unique('products', 'sku')->ignore($product?->id),
            ],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'compare_at_price' => ['nullable', 'numeric', 'min:0', 'gt:price'],
            'compare_at_markup_percent' => ['nullable', 'numeric', 'min:0', 'max:9999'],
            'color' => ['required', 'string', 'max:64'],
            'diameter' => ['required', 'numeric', 'min:0'],
            'weight_grams' => ['required', 'integer', 'min:1'],
            'stock_quantity' => ['sometimes', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],

            // legacy single image upload
            'image' => ['nullable', 'file', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
            'remove_image' => ['sometimes', 'boolean'],

            // gallery images upload
            'images' => ['nullable', 'array'],
            'images.*' => ['file', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
            'remove_images' => ['sometimes', 'array'],
            'remove_images.*' => ['string', 'max:255'],
            'image_order' => ['sometimes', 'array'],
            'image_order.*' => ['string', 'max:255'],
            'gallery_sequence' => ['sometimes', 'array'],
            'gallery_sequence.*' => ['string', 'max:255'],

            'video' => ['nullable', 'file', 'mimes:mp4,webm,mov,quicktime', 'max:51200'],
            'remove_video' => ['sometimes', 'boolean'],
        ]);

        return [
            'category_id' => $request->integer('category_id'),
            'name' => $request->string('name')->toString(),
            'sku' => $request->string('sku')->toString(),
            'description' => $request->input('description'),
            'price' => $request->input('price'),
            'compare_at_price' => $request->filled('compare_at_price') ? $request->input('compare_at_price') : null,
            'compare_at_markup_percent' => $request->filled('compare_at_markup_percent')
                ? $request->input('compare_at_markup_percent')
                : null,
            'color' => $request->string('color')->toString(),
            'diameter' => $request->input('diameter'),
            'weight_grams' => $request->integer('weight_grams'),
            'stock_quantity' => $request->integer('stock_quantity', $product?->stock_quantity ?? 0),
            'is_active' => $request->boolean('is_active', true),
        ];
    }

    private function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $counter = 1;

        while (
            Product::query()
                ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
                ->where('slug', $slug)
                ->exists()
        ) {
            $slug = "{$base}-{$counter}";
            $counter++;
        }

        return $slug;
    }

    private function deleteFile(?string $path): void
    {
        if ($path) {
            Storage::disk('public')->delete($path);
        }
    }

    /**
     * @return array<int, \Illuminate\Http\UploadedFile>
     */
    private function uploadedImageFiles(Request $request): array
    {
        $files = $request->file('images');

        if (!$files) {
            return [];
        }

        return array_values(is_array($files) ? $files : [$files]);
    }

    /**
     * @param  array<int, string>  $sequence
     * @param  array<int, string>  $existingImages
     * @param  array<int, string>  $storedNewImages
     * @return array<int, string>
     */
    private function mergeGallerySequence(array $sequence, array $existingImages, array $storedNewImages): array
    {
        $final = [];
        $usedExisting = [];
        $usedNew = [];

        foreach ($sequence as $token) {
            if (!is_string($token)) {
                continue;
            }

            if (str_starts_with($token, 'existing:')) {
                $path = substr($token, 9);

                if ($path !== '' && in_array($path, $existingImages, true) && !in_array($path, $usedExisting, true)) {
                    $final[] = $path;
                    $usedExisting[] = $path;
                }

                continue;
            }

            if (str_starts_with($token, 'new:')) {
                $index = (int) substr($token, 4);

                if (isset($storedNewImages[$index]) && !in_array($index, $usedNew, true)) {
                    $final[] = $storedNewImages[$index];
                    $usedNew[] = $index;
                }
            }
        }

        foreach ($existingImages as $path) {
            if (!in_array($path, $final, true)) {
                $final[] = $path;
            }
        }

        foreach ($storedNewImages as $index => $path) {
            if (!in_array($index, $usedNew, true)) {
                $final[] = $path;
            }
        }

        return $final;
    }
}

