<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class NavMenuAdController extends Controller
{
    // Emplacements publicitaires disponibles : un par menu de la navbar publique,
    // avec le texte par defaut actuellement affiche (pour ne rien changer tant
    // que l'admin n'a pas personnalise l'emplacement).
    private const SLOTS = [
        'abi' => [
            'label' => 'ABI',
            'default_text' => "Decouvrez l'univers ABI, sa vision, ses agences et l'ecosysteme qui accompagne tous vos projets immobiliers, construction et investissement.",
        ],
        'immobilier' => [
            'label' => 'Immobilier',
            'default_text' => "Trouvez un bien, valorisez un patrimoine et accedez à une offre immobiliere structurée pour l'achat, la vente et la location.",
        ],
        'construction' => [
            'label' => 'Construction',
            'default_text' => "Construisez, finalisez ou faites évoluer votre projet avec une offre plus complète, lisible et adaptée à chaque étape.",
        ],
        'investissement' => [
            'label' => 'Investissement',
            'default_text' => "Analysez les opportunités, comprenez les parcours d'investissement et structurez une stratégie de placement claire avec ABI.",
        ],
    ];

    private function getSlots(): array
    {
        $stored = Setting::get('nav_menu_ads', []);
        $stored = is_array($stored) ? $stored : [];

        $slots = [];
        foreach (self::SLOTS as $key => $meta) {
            $entry = $stored[$key] ?? [];
            $mode = ($entry['mode'] ?? 'text') === 'image' ? 'image' : 'text';
            $imagePath = $entry['image_path'] ?? null;

            $slots[] = [
                'key' => $key,
                'label' => $meta['label'],
                'mode' => $mode,
                'text' => $entry['text'] ?? $meta['default_text'],
                'image_url' => $this->resolveMediaUrl($imagePath),
            ];
        }

        return $slots;
    }

    // Public - consomme par la navbar du site public
    public function index()
    {
        return response()->json([
            'success' => true,
            'data' => $this->getSlots(),
        ]);
    }

    // Admin - meme contenu, pour l'ecran de gestion
    public function adminIndex()
    {
        return response()->json([
            'success' => true,
            'data' => $this->getSlots(),
        ]);
    }

    // Admin - met a jour un seul emplacement (texte ou image)
    public function update(Request $request)
    {
        $validated = $request->validate([
            'key' => ['required', Rule::in(array_keys(self::SLOTS))],
            'mode' => ['required', Rule::in(['text', 'image'])],
            'text' => 'nullable|string|max:600',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
            'remove_image' => 'nullable|boolean',
        ]);

        if ($validated['mode'] === 'text' && !filled($validated['text'] ?? null)) {
            return response()->json([
                'success' => false,
                'errors' => ['text' => ["Le texte est requis lorsque le mode 'texte' est actif."]],
            ], 422);
        }

        $stored = Setting::get('nav_menu_ads', []);
        $stored = is_array($stored) ? $stored : [];
        $key = $validated['key'];
        $entry = $stored[$key] ?? [];

        if ($request->boolean('remove_image') && !empty($entry['image_path'])) {
            Storage::disk('public')->delete($entry['image_path']);
            $entry['image_path'] = null;
        }

        if ($request->hasFile('image')) {
            if (!empty($entry['image_path'])) {
                Storage::disk('public')->delete($entry['image_path']);
            }
            $entry['image_path'] = $request->file('image')->store("nav-ads/{$key}", 'public');
        }

        if ($validated['mode'] === 'image' && empty($entry['image_path'])) {
            return response()->json([
                'success' => false,
                'errors' => ['image' => ["Une image est requise lorsque le mode 'image' est actif."]],
            ], 422);
        }

        $entry['mode'] = $validated['mode'];
        $entry['text'] = $validated['text'] ?? ($entry['text'] ?? null);

        $stored[$key] = $entry;
        Setting::set('nav_menu_ads', $stored, 'json');

        return response()->json([
            'success' => true,
            'data' => $this->getSlots(),
        ]);
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
