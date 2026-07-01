<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    public function index(): JsonResponse
    {
        $categories = Category::query()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return CategoryResource::collection($categories)->response();
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validatedData($request);

        $category = Category::create([
            'name' => $data['name'],
            'home_title' => $data['home_title'] ?? null,
            'home_bg_color' => $data['home_bg_color'] ?? null,
            'home_bg_color_end' => $data['home_bg_color_end'] ?? null,
            'slug' => $this->uniqueSlug($data['name']),
            'sort_order' => $data['sort_order'] ?? 0,
            'image' => $this->storeImage($request),
            'advantages' => $this->processAdvantages($request),
        ]);

        return (new CategoryResource($category))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Category $category): JsonResponse
    {
        return (new CategoryResource($category))->response();
    }

    public function update(Request $request, Category $category): JsonResponse
    {
        $data = $this->validatedData($request, $category);

        if ($request->hasFile('image')) {
            $this->deleteFile($category->image);
            $category->image = $this->storeImage($request);
        }

        $category->fill([
            'name' => $data['name'],
            'home_title' => $data['home_title'] ?? null,
            'home_bg_color' => $data['home_bg_color'] ?? null,
            'home_bg_color_end' => $data['home_bg_color_end'] ?? null,
            'sort_order' => $data['sort_order'] ?? $category->sort_order,
            'advantages' => $this->processAdvantages($request, $category->advantages ?? []),
        ]);

        if ($category->isDirty('name')) {
            $category->slug = $this->uniqueSlug($data['name'], $category->id);
        }

        $category->save();

        return (new CategoryResource($category))->response();
    }

    public function destroy(Category $category): JsonResponse
    {
        if ($category->products()->exists()) {
            return response()->json([
                'message' => 'Нельзя удалить категорию, в которой есть товары.',
            ], 422);
        }

        $this->deleteCategoryFiles($category);
        $category->delete();

        return response()->json(['message' => 'Deleted']);
    }

    private function validatedData(Request $request, ?Category $category = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'home_title' => ['nullable', 'string', 'max:255'],
            'home_bg_color' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'home_bg_color_end' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'image' => ['nullable', 'file', 'mimes:jpeg,jpg,png,webp,svg', 'max:4096'],
            'advantages' => ['required', 'array', 'size:3'],
            'advantages.*.text' => ['required', 'string', 'max:255'],
            'advantages.*.icon' => ['nullable', 'file', 'mimes:svg,png,jpg,jpeg,webp', 'max:2048'],
        ]);
    }

    private function storeImage(Request $request): ?string
    {
        if (! $request->hasFile('image')) {
            return null;
        }

        return $request->file('image')->store('categories/images', 'public');
    }

    private function processAdvantages(Request $request, array $existing = []): array
    {
        $advantages = [];

        for ($index = 0; $index < 3; $index++) {
            $text = $request->input("advantages.{$index}.text");
            $iconPath = $existing[$index]['icon'] ?? null;

            if ($request->hasFile("advantages.{$index}.icon")) {
                $this->deleteFile($iconPath);
                $iconPath = $request->file("advantages.{$index}.icon")->store('categories/icons', 'public');
            }

            $advantages[] = [
                'text' => $text,
                'icon' => $iconPath,
            ];
        }

        return $advantages;
    }

    private function deleteCategoryFiles(Category $category): void
    {
        $this->deleteFile($category->image);

        foreach ($category->advantages ?? [] as $advantage) {
            $this->deleteFile($advantage['icon'] ?? null);
        }
    }

    private function deleteFile(?string $path): void
    {
        if ($path) {
            Storage::disk('public')->delete($path);
        }
    }

    private function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $counter = 1;

        while (
            Category::where('slug', $slug)
                ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $slug = "{$base}-{$counter}";
            $counter++;
        }

        return $slug;
    }
}
