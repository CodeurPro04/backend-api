<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class AIChatController extends Controller
{
    /**
     * Configuration des agents IA ABI.
     * Chaque agent a son modèle Ollama et son prompt système.
     */
    private function getAgents(): array
    {
        return [
            'akapko' => [
                'name'   => 'Akapko Manawa',
                'model'  => config('services.ollama.models.construction', 'llama3'),
                'system' => "Tu es Akapko Manawa, expert en construction en Afrique pour la plateforme ABI (Africa Build Invest). ".
                            "Tu conseilles les clients sur leurs projets de construction sur mesure, les plans de maison, les devis, ".
                            "les matériaux locaux, les promoteurs constructeurs partenaires et les étapes administratives. ".
                            "Tu t'exprimes en français, de manière professionnelle mais accessible. ".
                            "Si une question dépasse ton domaine (construction), oriente vers l'agent approprié. ".
                            "Tu connais le catalogue de modèles de maison ABI et les projets de construction disponibles sur la plateforme.",
            ],
            'djuedjue' => [
                'name'   => 'Djuêdjuê',
                'model'  => config('services.ollama.models.immobilier', 'llama3'),
                'system' => "Tu es Djuêdjuê, expert en immobilier résidentiel et commercial en Afrique pour la plateforme ABI (Africa Build Invest). ".
                            "Tu accompagnes les clients pour acheter, vendre ou louer un bien immobilier en Afrique. ".
                            "Tu conseilles sur les prix du marché, les quartiers, les titres fonciers, les démarches légales et les agents certifiés ABI. ".
                            "Tu t'exprimes en français, de manière professionnelle mais chaleureuse. ".
                            "Tu connais toutes les propriétés disponibles sur la plateforme ABI et peux orienter vers les agents immobiliers certifiés.",
            ],
            'koffi' => [
                'name'   => 'Koffi Gombo',
                'model'  => config('services.ollama.models.investissement', 'llama3'),
                'system' => "Tu es Koffi Gombo, conseiller en investissement immobilier en Afrique pour la plateforme ABI (Africa Build Invest). ".
                            "Tu aides les investisseurs à identifier les meilleures opportunités d'investissement immobilier en Afrique, ".
                            "à analyser les rendements locatifs, à comprendre les risques et à diversifier leur portefeuille. ".
                            "Tu expliques les projets d'investissement disponibles sur ABI, les modalités de financement et les perspectives de rentabilité. ".
                            "Tu t'exprimes en français, avec un ton expert et rassurant. ".
                            "Tu rappelles toujours que tout investissement comporte des risques et conseilles de consulter un expert financier.",
            ],
        ];
    }

    /**
     * POST /api/v1/ai/chat
     */
    public function chat(Request $request)
    {
        $validated = $request->validate([
            'agent'           => 'required|string|in:akapko,djuedjue,koffi',
            'message'         => 'required|string|max:2000',
            'conversation_id' => 'nullable|string|max:100',
            'history'         => 'nullable|array|max:20',
            'history.*.role'  => 'required_with:history|string|in:user,assistant',
            'history.*.content' => 'required_with:history|string|max:2000',
        ]);

        $agents = $this->getAgents();
        $agent  = $agents[$validated['agent']];

        // Construction du tableau de messages (system + historique + message actuel)
        $messages = [
            ['role' => 'system', 'content' => $agent['system']],
        ];

        if (!empty($validated['history'])) {
            foreach ($validated['history'] as $msg) {
                $messages[] = [
                    'role'    => $msg['role'],
                    'content' => $msg['content'],
                ];
            }
        }

        $messages[] = [
            'role'    => 'user',
            'content' => $validated['message'],
        ];

        $ollamaUrl = config('services.ollama.url', 'http://localhost:11434');

        try {
            $response = Http::timeout(60)
                ->post("{$ollamaUrl}/api/chat", [
                    'model'    => $agent['model'],
                    'messages' => $messages,
                    'stream'   => false,
                    'options'  => [
                        'temperature' => 0.7,
                        'num_predict' => 500,
                    ],
                ]);

            if (!$response->successful()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Le service IA est temporairement indisponible. Veuillez réessayer.',
                ], 503);
            }

            $reply = $response->json('message.content');

            if (empty($reply)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Réponse invalide du service IA.',
                ], 500);
            }

            return response()->json([
                'success' => true,
                'data'    => [
                    'message'         => $reply,
                    'conversation_id' => $validated['conversation_id'] ?? (string) Str::uuid(),
                    'agent'           => $validated['agent'],
                ],
            ]);

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Impossible de joindre le service IA. Vérifiez que Ollama est bien démarré sur le serveur.',
            ], 503);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Une erreur inattendue est survenue.',
            ], 500);
        }
    }
}
