<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Message;
use App\Models\User;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class MessageController extends Controller
{
    /**
     * Liste des messages de l'utilisateur
     */
    public function index(Request $request)
    {
        try {
            $messages = Message::with(['sender', 'recipient', 'property'])
                ->forUser($request->user()->id)
                ->orderBy('created_at', 'desc')
                ->paginate(20);

            return response()->json([
                'success' => true,
                'data' => $messages
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération'
            ], 500);
        }
    }

    /**
     * Envoyer un message
     */
    public function send(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'recipient_id' => 'required|exists:users,id',
            'property_id' => 'nullable|exists:properties,id',
            'subject' => 'required|string|max:255',
            'message' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $message = Message::create([
                'sender_id' => $request->user()->id,
                'recipient_id' => $request->recipient_id,
                'property_id' => $request->property_id,
                'subject' => $request->subject,
                'message' => $request->message,
            ]);

            // Créer une notification pour le destinataire
            Notification::create([
                'user_id' => $request->recipient_id,
                'type' => 'message_received',
                'title' => 'Nouveau message',
                'message' => "Vous avez reçu un message de {$request->user()->full_name}",
                'data' => json_encode(['message_uuid' => $message->uuid]),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Message envoyé avec succès',
                'data' => $message
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'envoi'
            ], 500);
        }
    }

    /**
     * Détails d'un message
     */
    public function show($uuid)
    {
        try {
            $message = Message::with(['sender', 'recipient', 'property', 'replies'])
                ->where('uuid', $uuid)
                ->firstOrFail();

            // Marquer comme lu si c'est le destinataire
            if ($message->recipient_id === auth()->id()) {
                $message->markAsRead();
            }

            return response()->json([
                'success' => true,
                'data' => $message
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Message non trouvé'
            ], 404);
        }
    }

    /**
     * Répondre à un message
     */
    public function reply(Request $request, $uuid)
    {
        $validator = Validator::make($request->all(), [
            'message' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $parentMessage = Message::where('uuid', $uuid)->firstOrFail();

            $reply = Message::create([
                'sender_id' => $request->user()->id,
                'recipient_id' => $parentMessage->sender_id,
                'property_id' => $parentMessage->property_id,
                'subject' => 'RE: ' . $parentMessage->subject,
                'message' => $request->message,
                'parent_message_id' => $parentMessage->id,
            ]);

            // Notification
            Notification::create([
                'user_id' => $parentMessage->sender_id,
                'type' => 'message_reply',
                'title' => 'Nouvelle réponse',
                'message' => "Vous avez reçu une réponse de {$request->user()->full_name}",
                'data' => json_encode(['message_uuid' => $reply->uuid]),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Réponse envoyée',
                'data' => $reply
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la réponse'
            ], 500);
        }
    }

    /**
     * Messages pour les agents
     */
    public function agentMessages(Request $request)
    {
        try {
            $messages = Message::with(['sender', 'property'])
                ->where('recipient_id', $request->user()->id)
                ->orderBy('is_read', 'asc')
                ->orderBy('created_at', 'desc')
                ->paginate(20);

            return response()->json([
                'success' => true,
                'data' => $messages
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur'
            ], 500);
        }
    }
}