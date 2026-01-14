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
    private function createReplyMessage($senderId, Message $parentMessage, string $content, string $subjectPrefix = 'RE: ')
    {
        return Message::create([
            'sender_id' => $senderId,
            'recipient_id' => $parentMessage->sender_id,
            'property_id' => $parentMessage->property_id,
            'subject' => $subjectPrefix . $parentMessage->subject,
            'message' => $content,
            'parent_message_id' => $parentMessage->id,
        ]);
    }

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

            $reply = $this->createReplyMessage($request->user()->id, $parentMessage, $request->message);

            // Notification
            try {
                Notification::create([
                    'user_id' => $parentMessage->sender_id,
                    'type' => 'message_reply',
                    'title' => 'Nouvelle r?ponse',
                    'message' => "Vous avez re?u une r?ponse de {$request->user()->full_name}",
                    'data' => json_encode(['message_uuid' => $reply->uuid]),
                ]);
            } catch (\Throwable $e) {
                logger()->error('Notification reply failed', [
                    'error' => $e->getMessage(),
                    'message_uuid' => $reply->uuid,
                ]);
            }

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

    /**
     * RÇ¸pondre Çÿ un message (agent)
     */
    public function respond(Request $request, $uuid)
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

            $reply = $this->createReplyMessage($request->user()->id, $parentMessage, $request->message);

            try {
                Notification::create([
                    'user_id' => $parentMessage->sender_id,
                    'type' => 'message_reply',
                    'title' => 'Nouvelle r??ponse',
                    'message' => "Vous avez re??u une r??ponse de {$request->user()->full_name}",
                    'data' => json_encode(['message_uuid' => $reply->uuid]),
                ]);
            } catch (\Throwable $e) {
                logger()->error('Agent notification reply failed', [
                    'error' => $e->getMessage(),
                    'message_uuid' => $reply->uuid,
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'RÇ¸ponse envoyÇ¸e',
                'data' => $reply
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la rÇ¸ponse'
            ], 500);
        }
    }

    /**
     * Marquer comme lu (agent)
     */
    public function agentMarkRead(Request $request, $uuid)
    {
        try {
            $message = Message::where('uuid', $uuid)
                ->where('recipient_id', $request->user()->id)
                ->firstOrFail();
            $message->markAsRead();

            return response()->json([
                'success' => true,
                'message' => 'Message marquÇ¸ comme lu'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur'
            ], 500);
        }
    }

    /**
     * Messages pour propriÇ¸taire
     */
    public function ownerMessages(Request $request)
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

    public function ownerShow(Request $request, $uuid)
    {
        try {
            $message = Message::with(['sender', 'property', 'replies'])
                ->where('uuid', $uuid)
                ->where('recipient_id', $request->user()->id)
                ->firstOrFail();

            $message->markAsRead();

            return response()->json([
                'success' => true,
                'data' => $message
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Message non trouvÇ¸'
            ], 404);
        }
    }

    public function ownerReply(Request $request, $uuid)
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
            $parentMessage = Message::where('uuid', $uuid)
                ->where('recipient_id', $request->user()->id)
                ->firstOrFail();

            $reply = $this->createReplyMessage($request->user()->id, $parentMessage, $request->message);

            Notification::create([
                'user_id' => $parentMessage->sender_id,
                'type' => 'message_reply',
                'title' => 'Nouvelle rÇ¸ponse',
                'message' => "Vous avez reÇõu une rÇ¸ponse de {$request->user()->full_name}",
                'data' => json_encode(['message_uuid' => $reply->uuid]),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'RÇ¸ponse envoyÇ¸e',
                'data' => $reply
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la rÇ¸ponse'
            ], 500);
        }
    }

    public function ownerMarkRead(Request $request, $uuid)
    {
        try {
            $message = Message::where('uuid', $uuid)
                ->where('recipient_id', $request->user()->id)
                ->firstOrFail();
            $message->markAsRead();

            return response()->json([
                'success' => true,
                'message' => 'Message marquÇ¸ comme lu'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur'
            ], 500);
        }
    }

    public function ownerDelete(Request $request, $uuid)
    {
        try {
            $message = Message::where('uuid', $uuid)
                ->where('recipient_id', $request->user()->id)
                ->firstOrFail();
            $message->delete();

            return response()->json([
                'success' => true,
                'message' => 'Message supprimÇ¸'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur'
            ], 500);
        }
    }

    /**
     * Messages pour admin
     */
    public function adminIndex(Request $request)
    {
        try {
            $messages = Message::with(['sender', 'recipient', 'property'])
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

    public function adminShow($uuid)
    {
        try {
            $message = Message::with(['sender', 'recipient', 'property', 'replies'])
                ->where('uuid', $uuid)
                ->firstOrFail();

            return response()->json([
                'success' => true,
                'data' => $message
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Message non trouvÇ¸'
            ], 404);
        }
    }

    public function adminCreate(Request $request)
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

            return response()->json([
                'success' => true,
                'data' => $message
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la crÇ¸ation'
            ], 500);
        }
    }

    public function adminUpdate(Request $request, $uuid)
    {
        $validator = Validator::make($request->all(), [
            'subject' => 'sometimes|string|max:255',
            'message' => 'sometimes|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $message = Message::where('uuid', $uuid)->firstOrFail();
            $message->update($request->only(['subject', 'message']));

            return response()->json([
                'success' => true,
                'data' => $message
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur'
            ], 500);
        }
    }

    public function adminDestroy($uuid)
    {
        try {
            $message = Message::where('uuid', $uuid)->firstOrFail();
            $message->delete();

            return response()->json([
                'success' => true,
                'message' => 'Message supprimÇ¸'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur'
            ], 500);
        }
    }

    public function adminMarkRead($uuid)
    {
        try {
            $message = Message::where('uuid', $uuid)->firstOrFail();
            $message->markAsRead();

            return response()->json([
                'success' => true,
                'message' => 'Message marquÇ¸ comme lu'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur'
            ], 500);
        }
    }

    public function adminReply(Request $request, $uuid)
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
            $reply = $this->createReplyMessage($request->user()->id, $parentMessage, $request->message);

            Notification::create([
                'user_id' => $parentMessage->sender_id,
                'type' => 'message_reply',
                'title' => 'Nouvelle rÇ¸ponse',
                'message' => "Vous avez reÇõu une rÇ¸ponse de {$request->user()->full_name}",
                'data' => json_encode(['message_uuid' => $reply->uuid]),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'RÇ¸ponse envoyÇ¸e',
                'data' => $reply
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la rÇ¸ponse'
            ], 500);
        }
    }
}
