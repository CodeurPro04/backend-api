<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;

class PresentationVideoController extends Controller
{
    private function getContent(): array
    {
        return [
            'title' => Setting::get('presentation_video_title', 'Videos de présentation'),
            'description' => Setting::get(
                'presentation_video_description',
                "Consulte les contenus video ajoutes depuis l'espace administrateur pour decouvrir l'univers ABI et ses modèles."
            ),
            'videos' => Setting::get('presentation_video_urls', [
                'https://www.youtube.com/watch?v=tgbNymZ7vqY',
            ]),
        ];
    }

    // Public - contenu affiche sur la page d'accueil
    public function show()
    {
        return response()->json([
            'success' => true,
            'data' => $this->getContent(),
        ]);
    }

    // Admin - mise a jour du titre, sous-titre et des liens video
    public function update(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string|max:2000',
            'video_urls' => 'nullable|array',
            'video_urls.*' => 'nullable|string|max:2000',
        ]);

        Setting::set('presentation_video_title', $validated['title']);
        Setting::set('presentation_video_description', $validated['description']);
        Setting::set(
            'presentation_video_urls',
            array_values(array_filter($validated['video_urls'] ?? [], fn ($url) => filled($url))),
            'json'
        );

        return response()->json([
            'success' => true,
            'data' => $this->getContent(),
        ]);
    }
}
