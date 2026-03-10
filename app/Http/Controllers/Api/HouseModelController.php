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
            'showcase_sections' => 'nullable|array|size:3',
            'showcase_sections.*.title' => 'required|string|max:255',
            'showcase_sections.*.button_label' => 'required|string|max:120',
            'showcase_sections.*.button_link' => 'required|string|max:1000',
            'showcase_sections.*.items' => 'required|array|min:1',
            'showcase_sections.*.items.*.title' => 'required|string|max:255',
            'showcase_sections.*.items.*.excerpt' => 'required|string|max:1000',
            'showcase_sections.*.items.*.image_url' => 'required|string|max:2000',
            'showcase_sections.*.items.*.link' => 'nullable|string|max:1000',
        ]);

        Setting::set('house_models_section_title', $validated['section_title']);
        Setting::set('house_models_section_description', $validated['section_description']);
        Setting::set(
            'house_models_section_videos',
            array_values(array_filter($validated['video_urls'] ?? [], fn ($url) => filled($url))),
            'json'
        );
        Setting::set(
            'house_models_showcase_sections',
            $validated['showcase_sections'] ?? $this->defaultShowcaseSections(),
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
        $storedShowcaseSections = Setting::get(
            'house_models_showcase_sections',
            $this->defaultShowcaseSections()
        );

        return [
            'title' => Setting::get('house_models_section_title', 'Modeles de maison'),
            'description' => Setting::get(
                'house_models_section_description',
                'Decouvrez nos modeles de maison, pensés pour allier style, confort et fonctionnalite dans chaque projet.'
            ),
            'videos' => Setting::get('house_models_section_videos', [
                'https://www.youtube.com/watch?v=tgbNymZ7vqY',
            ]),
            'showcase_sections' => $this->normalizeShowcaseSections($storedShowcaseSections),
        ];
    }
    private function defaultShowcaseSections(): array
    {
        return [
            [
                'title' => "Besoin d'un bien",
                'button_label' => 'Voir tous les articles',
                'button_link' => '/properties',
                'items' => [
                    [
                        'title' => 'Vous faites construire ?',
                        'excerpt' => 'Les points cles pour choisir le bon constructeur et avancer sereinement dans votre projet.',
                        'image_url' => 'https://images.unsplash.com/photo-1504307651254-35680f356dfd?w=1200&q=80',
                        'link' => '/construction',
                    ],
                    [
                        'title' => 'Panneaux solaires',
                        'excerpt' => 'Des solutions durables pour mieux valoriser un projet immobilier ou une maison.',
                        'image_url' => 'https://images.unsplash.com/photo-1509391366360-2e959784a276?w=1200&q=80',
                        'link' => '/construction',
                    ],
                    [
                        'title' => 'Design et confort',
                        'excerpt' => 'Des choix simples pour allier esthetique, confort et fonctionnalite dans votre bien.',
                        'image_url' => 'https://images.unsplash.com/photo-1513694203232-719a280e022f?w=1200&q=80',
                        'link' => '/house-models',
                    ],
                    [
                        'title' => 'Marches et opportunites',
                        'excerpt' => 'Identifiez les zones et les tendances qui comptent vraiment pour votre investissement.',
                        'image_url' => 'https://images.unsplash.com/photo-1460317442991-0ec209397118?w=1200&q=80',
                        'link' => '/investment',
                    ],
                ],
            ],
            [
                'title' => "Besoin d'un projet de construction",
                'button_label' => 'Voir tous les projets',
                'button_link' => '/construction',
                'items' => [
                    [
                        'title' => 'Construire sa maison',
                        'excerpt' => 'Les etapes essentielles pour bien preparer votre projet de construction.',
                        'image_url' => 'https://images.unsplash.com/photo-1504307651254-35680f356dfd?w=1200&q=80',
                        'link' => '/construction',
                    ],
                    [
                        'title' => 'Choisir son terrain',
                        'excerpt' => 'Les criteres a verifier avant de lancer les plans et les travaux.',
                        'image_url' => 'https://images.unsplash.com/photo-1489515217757-5fd1be406fef?w=1200&q=80',
                        'link' => '/construction',
                    ],
                    [
                        'title' => 'Budget et financement',
                        'excerpt' => 'Maitrisez votre budget de construction et anticipez les couts annexes.',
                        'image_url' => 'https://images.unsplash.com/photo-1554224155-6726b3ff858f?w=1200&q=80',
                        'link' => '/construction',
                    ],
                    [
                        'title' => 'Normes et qualite',
                        'excerpt' => 'Comprenez les garanties et standards pour un chantier serein.',
                        'image_url' => 'https://images.unsplash.com/photo-1503387762-592deb58ef4e?w=1200&q=80',
                        'link' => '/construction',
                    ],
                ],
            ],
            [
                'title' => "J'investis dans un projet",
                'button_label' => 'Voir les opportunites',
                'button_link' => '/investment',
                'items' => [
                    [
                        'title' => 'Investissement locatif',
                        'excerpt' => 'Identifiez les meilleurs emplacements pour un rendement durable.',
                        'image_url' => 'https://images.unsplash.com/photo-1460317442991-0ec209397118?w=1200&q=80',
                        'link' => '/investment',
                    ],
                    [
                        'title' => 'Diversifier son portefeuille',
                        'excerpt' => 'Immobilier residentiel, commercial ou terrain: les options a considerer.',
                        'image_url' => 'https://images.unsplash.com/photo-1454165804606-c3d57bc86b40?w=1200&q=80',
                        'link' => '/investment',
                    ],
                    [
                        'title' => 'Analyse des risques',
                        'excerpt' => 'Les indicateurs cles pour securiser vos decisions d investissement.',
                        'image_url' => 'https://images.unsplash.com/photo-1460925895917-afdab827c52f?w=1200&q=80',
                        'link' => '/investment',
                    ],
                    [
                        'title' => 'Opportunites du marche',
                        'excerpt' => 'Profitez des tendances et projets porteurs dans votre zone cible.',
                        'image_url' => 'https://images.unsplash.com/photo-1551836022-deb4988cc6c0?w=1200&q=80',
                        'link' => '/investment',
                    ],
                ],
            ],
        ];
    }

    private function normalizeShowcaseSections(mixed $sections): array
    {
        $defaults = $this->defaultShowcaseSections();
        if (!is_array($sections)) {
            return $defaults;
        }

        $normalized = [];
        foreach ($defaults as $sectionIndex => $defaultSection) {
            $currentSection = $sections[$sectionIndex] ?? [];
            if (!is_array($currentSection)) {
                $currentSection = [];
            }

            $currentItems = $currentSection['items'] ?? [];
            if (!is_array($currentItems)) {
                $currentItems = [];
            }

            $itemsSource = count($currentItems) > 0 ? $currentItems : ($defaultSection['items'] ?? []);

            $items = [];
            foreach ($itemsSource as $itemIndex => $rawItem) {
                $currentItem = is_array($rawItem) ? $rawItem : [];
                $defaultItem = $defaultSection['items'][$itemIndex] ?? [
                    'title' => '',
                    'excerpt' => '',
                    'image_url' => '',
                    'link' => '',
                ];

                $items[] = [
                    'title' => $currentItem['title'] ?? $defaultItem['title'],
                    'excerpt' => $currentItem['excerpt'] ?? $defaultItem['excerpt'],
                    'image_url' => $currentItem['image_url'] ?? $defaultItem['image_url'],
                    'link' => $currentItem['link'] ?? $defaultItem['link'],
                ];
            }

            $normalized[] = [
                'title' => $currentSection['title'] ?? $defaultSection['title'],
                'button_label' => $currentSection['button_label'] ?? $defaultSection['button_label'],
                'button_link' => $currentSection['button_link'] ?? $defaultSection['button_link'],
                'items' => $items,
            ];
        }

        return $normalized;
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

