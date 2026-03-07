<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\HouseModel;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class HouseModelController extends Controller
{
    public function index()
    {
        $models = HouseModel::where('is_active', true)
            ->orderBy('display_order')
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'success' => true,
            'section' => $this->getSectionContent(),
            'data' => $models->map(fn (HouseModel $model) => $this->transform($model)),
        ]);
    }

    public function show(string $identifier)
    {
        $model = HouseModel::where(function ($query) use ($identifier) {
            $query->where('uuid', $identifier)->orWhere('slug', $identifier);
        })
            ->where('is_active', true)
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'data' => $this->transform($model),
        ]);
    }

    public function adminIndex()
    {
        $models = HouseModel::orderBy('display_order')
            ->orderByDesc('updated_at')
            ->get();

        return response()->json([
            'success' => true,
            'section' => $this->getSectionContent(),
            'data' => $models->map(fn (HouseModel $model) => $this->transform($model)),
        ]);
    }

    public function updateSection(Request $request)
    {
        $validated = $request->validate([
            'section_title' => 'required|string|max:255',
            'section_description' => 'required|string|max:2000',
            'video_urls' => 'nullable|array',
            'video_urls.*' => 'nullable|string|max:2000',
        ]);

        Setting::set('house_models_section_title', $validated['section_title']);
        Setting::set('house_models_section_description', $validated['section_description']);
        Setting::set(
            'house_models_section_videos',
            array_values(array_filter($validated['video_urls'] ?? [], fn ($url) => filled($url))),
            'json'
        );

        return response()->json([
            'success' => true,
            'section' => $this->getSectionContent(),
        ]);
    }

    public function store(Request $request)
    {
        if ($request->has('is_active')) {
            $request->merge([
                'is_active' => filter_var($request->input('is_active'), FILTER_VALIDATE_BOOLEAN),
            ]);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'short_description' => 'nullable|string',
            'description' => 'nullable|string',
            'display_order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
            'cover_image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
            'gallery_images' => 'nullable|array',
            'gallery_images.*' => 'image|mimes:jpg,jpeg,png,webp|max:4096',
        ]);

        $model = HouseModel::create([
            'uuid' => (string) Str::uuid(),
            'created_by' => $request->user()?->id,
            'title' => $validated['title'],
            'slug' => Str::slug($validated['title']) . '-' . Str::random(6),
            'short_description' => $validated['short_description'] ?? null,
            'description' => $validated['description'] ?? null,
            'display_order' => $validated['display_order'] ?? 0,
            'is_active' => $validated['is_active'] ?? true,
            'gallery_images' => [],
        ]);

        if ($request->hasFile('cover_image')) {
            $model->cover_image = $request->file('cover_image')
                ->store("house-models/{$model->uuid}/cover", 'public');
        }

        if ($request->hasFile('gallery_images')) {
            $gallery = [];
            foreach ($request->file('gallery_images') as $image) {
                $gallery[] = $image->store("house-models/{$model->uuid}/gallery", 'public');
            }
            $model->gallery_images = $gallery;
        }

        $model->save();

        return response()->json([
            'success' => true,
            'data' => $this->transform($model->fresh()),
        ], 201);
    }

    public function update(Request $request, string $uuid)
    {
        if ($request->has('is_active')) {
            $request->merge([
                'is_active' => filter_var($request->input('is_active'), FILTER_VALIDATE_BOOLEAN),
            ]);
        }

        if ($request->has('remove_cover_image')) {
            $request->merge([
                'remove_cover_image' => filter_var($request->input('remove_cover_image'), FILTER_VALIDATE_BOOLEAN),
            ]);
        }

        $model = HouseModel::where('uuid', $uuid)->firstOrFail();

        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'short_description' => 'nullable|string',
            'description' => 'nullable|string',
            'display_order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
            'remove_cover_image' => 'nullable|boolean',
            'remove_gallery_images' => 'nullable|array',
            'remove_gallery_images.*' => 'string',
            'cover_image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
            'gallery_images' => 'nullable|array',
            'gallery_images.*' => 'image|mimes:jpg,jpeg,png,webp|max:4096',
        ]);

        $payload = $request->only([
            'title',
            'short_description',
            'description',
            'display_order',
            'is_active',
        ]);

        if (!empty($payload)) {
            $model->fill($payload);
            if (array_key_exists('title', $payload) && $payload['title']) {
                $model->slug = Str::slug($payload['title']) . '-' . Str::random(6);
            }
        }

        if (!empty($validated['remove_cover_image']) && $model->cover_image) {
            Storage::disk('public')->delete($model->cover_image);
            $model->cover_image = null;
        }

        $gallery = $model->gallery_images ?? [];
        $toRemove = $validated['remove_gallery_images'] ?? [];
        if (!empty($toRemove)) {
            foreach ($toRemove as $path) {
                Storage::disk('public')->delete($path);
            }
            $gallery = array_values(array_diff($gallery, $toRemove));
        }

        if ($request->hasFile('cover_image')) {
            if ($model->cover_image) {
                Storage::disk('public')->delete($model->cover_image);
            }
            $model->cover_image = $request->file('cover_image')
                ->store("house-models/{$model->uuid}/cover", 'public');
        }

        if ($request->hasFile('gallery_images')) {
            foreach ($request->file('gallery_images') as $image) {
                $gallery[] = $image->store("house-models/{$model->uuid}/gallery", 'public');
            }
        }

        $model->gallery_images = $gallery;
        $model->save();

        return response()->json([
            'success' => true,
            'data' => $this->transform($model->fresh()),
        ]);
    }

    public function destroy(string $uuid)
    {
        $model = HouseModel::where('uuid', $uuid)->firstOrFail();

        if ($model->cover_image) {
            Storage::disk('public')->delete($model->cover_image);
        }

        foreach (($model->gallery_images ?? []) as $path) {
            Storage::disk('public')->delete($path);
        }

        $model->delete();

        return response()->json([
            'success' => true,
            'message' => 'Modele supprime avec succes.',
        ]);
    }

    private function transform(HouseModel $model): array
    {
        $gallery = $model->gallery_images ?? [];

        return [
            'id' => $model->id,
            'uuid' => $model->uuid,
            'title' => $model->title,
            'slug' => $model->slug,
            'short_description' => $model->short_description,
            'description' => $model->description,
            'cover_image' => $model->cover_image,
            'cover_image_url' => $this->resolveMediaUrl($model->cover_image),
            'gallery_images' => $gallery,
            'gallery_image_urls' => array_values(array_filter(
                array_map(fn ($path) => $this->resolveMediaUrl($path), $gallery)
            )),
            'display_order' => $model->display_order,
            'is_active' => (bool) $model->is_active,
            'created_at' => $model->created_at,
            'updated_at' => $model->updated_at,
        ];
    }

    private function getSectionContent(): array
    {
        return [
            'title' => Setting::get('house_models_section_title', 'Modeles de maison'),
            'description' => Setting::get(
                'house_models_section_description',
                'Decouvrez nos modeles de maison, pensés pour allier style, confort et fonctionnalite dans chaque projet.'
            ),
            'videos' => Setting::get('house_models_section_videos', [
                'https://www.youtube.com/watch?v=tgbNymZ7vqY',
            ]),
        ];
    }

    private function resolveMediaUrl(?string $path): ?string
    {
        if (!$path) {
            return null;
        }

        if (preg_match('/^https?:\/\//i', $path)) {
            return $path;
        }

        $cleaned = preg_replace('/^public\//', '', $path);
        return url('/storage/' . ltrim($cleaned, '/'));
    }
}
