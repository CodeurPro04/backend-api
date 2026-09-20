<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Message;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class MessageController extends Controller
{
    private const MESSAGE_MAX_LENGTH = 5000;
    private const SUBJECT_MAX_LENGTH = 255;

    /**
     * Nettoie un contenu de message : retire les balises HTML/scripts et les
     * espaces superflus. Le contenu est stocke et affiche en texte brut.
     */
    private function sanitizeContent(string $value): string
    {
        return trim(strip_tags($value));
    }

    /**
     * Derive un intitule de conversation a partir du premier message, pour
     * les vues qui affichent encore un "objet" (listes agent/proprietaire).
     * L'utilisateur ne saisit plus de sujet : tout se passe dans le chat.
     */
    private function deriveSubject(string $content): string
    {
        $flat = trim(preg_replace('/\s+/', ' ', $content));
        if ($flat === '') {
            return 'Conversation';
        }
        return mb_strlen($flat) > 60 ? mb_substr($flat, 0, 57) . '...' : $flat;
    }

    /**
     * Cree une reponse et l'attache toujours a la racine du fil (et non au
     * message precis auquel on repond), de sorte que toute la conversation
     * reste groupee et s'affiche de maniere lineaire quel que soit le nombre
     * d'allers-retours. Le sujet reste celui de la conversation d'origine,
     * sans prefixe "RE:" : tout se lit comme une discussion continue.
     */
    private function createReplyMessage($senderId, Message $parentMessage, string $content): Message
    {
        $root = $parentMessage->threadRoot();

        // Le destinataire est l'autre participant du fil (et non systematiquement
        // l'auteur du message precis auquel on repond) : dans une vraie
        // conversation, chaque cote peut enchainer plusieurs messages avant que
        // l'autre ne reponde, donc "l'expediteur du message parent" ne designe
        // pas forcement le bon destinataire.
        $recipientId = $root->threadParticipantIds()
            ->first(fn ($id) => $id !== $senderId) ?? $parentMessage->sender_id;

        return Message::create([
            'sender_id' => $senderId,
            'recipient_id' => $recipientId,
            'property_id' => $root->property_id,
            'subject' => $root->subject,
            'message' => $this->sanitizeContent($content),
            'parent_message_id' => $root->id,
        ]);
    }

    private function notifyReply(Message $reply, $actor): void
    {
        try {
            Notification::create([
                'user_id' => $reply->recipient_id,
                'type' => 'message_reply',
                'title' => 'Nouvelle reponse',
                'message' => "Vous avez recu une reponse de {$actor->full_name}",
                'data' => json_encode(['message_uuid' => $reply->uuid]),
            ]);
        } catch (\Throwable $e) {
            Log::error('Notification message_reply failed', [
                'error' => $e->getMessage(),
                'message_uuid' => $reply->uuid,
            ]);
        }
    }

    /**
     * Verifie que l'utilisateur participe au fil de discussion (racine ou
     * n'importe quel message de la conversation), pas seulement au message
     * uuid precis demande. Retourne false si l'acces doit etre refuse.
     */
    private function assertParticipant(Message $message, ?int $userId): bool
    {
        if ($message->isParticipant($userId)) {
            return true;
        }
        return $message->threadParticipantIds()->contains($userId);
    }

    /**
     * Marque comme lus tous les messages du fil dont l'utilisateur est
     * destinataire (pas seulement la racine), pour qu'ouvrir une conversation
     * fasse disparaitre l'indicateur "non lu" sur l'ensemble de l'echange,
     * comme dans une vraie messagerie.
     */
    private function markThreadAsRead($thread, ?int $userId)
    {
        if (!$userId) {
            return $thread;
        }
        $thread->where('recipient_id', $userId)
            ->where('is_read', false)
            ->each(fn ($item) => $item->markAsRead());

        return $thread;
    }

    private function applyListFilters($query, Request $request)
    {
        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('subject', 'like', "%{$search}%")
                    ->orWhere('message', 'like', "%{$search}%");
            });
        }

        if ($request->get('status') === 'unread') {
            $query->where('is_read', false);
        } elseif ($request->get('status') === 'read') {
            $query->where('is_read', true);
        }

        return $query;
    }

    /**
     * Inclut/exclut les conversations archivees. Une conversation est
     * archivee via sa racine (parent_message_id null) ; un message reponse
     * est considere archive si le message racine dont il depend l'est.
     * Reserve a la vue d'ensemble admin : n'est jamais applique aux
     * boites de reception des utilisateurs finaux (owner/agent/index).
     */
    private function applyArchiveFilter($query, Request $request)
    {
        $wantArchived = $request->get('status') === 'archived';

        $query->where(function ($q) use ($wantArchived) {
            $q->where(function ($root) use ($wantArchived) {
                $root->whereNull('parent_message_id');
                $wantArchived ? $root->whereNotNull('archived_at') : $root->whereNull('archived_at');
            })->orWhere(function ($reply) use ($wantArchived) {
                $reply->whereNotNull('parent_message_id')
                    ->whereHas('parentMessage', function ($parent) use ($wantArchived) {
                        $wantArchived ? $parent->whereNotNull('archived_at') : $parent->whereNull('archived_at');
                    });
            });
        });

        return $query;
    }

    /**
     * Liste des agents actifs avec qui n'importe quel utilisateur connecte
     * peut demarrer une conversation (bouton "nouvelle conversation").
     */
    public function messageableAgents(Request $request)
    {
        try {
            $agents = User::whereHas('role', function ($query) {
                $query->where('slug', 'agent');
            })
                ->where('is_active', true)
                ->where('id', '!=', $request->user()->id)
                ->orderBy('first_name')
                ->get(['id', 'uuid', 'first_name', 'last_name', 'avatar', 'agent_type'])
                ->map(function ($agent) {
                    return [
                        'id' => $agent->id,
                        'uuid' => $agent->uuid,
                        'full_name' => trim($agent->first_name . ' ' . $agent->last_name),
                        'avatar' => $agent->avatar,
                        'agent_type' => $agent->agent_type,
                    ];
                });

            return response()->json(['success' => true, 'data' => $agents]);
        } catch (\Exception $e) {
            Log::error('Messageable agents list failed', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la recuperation des agents'
            ], 500);
        }
    }

    /**
     * Annuaire complet pour demarrer une conversation depuis le backoffice :
     * n'importe quel utilisateur actif (agent, admin, gestionnaire, partenaire,
     * visiteur, proprietaire, investisseur...), avec recherche par nom et
     * filtre optionnel par role.
     */
    public function messageableUsers(Request $request)
    {
        try {
            $query = User::with('role')
                ->where('is_active', true)
                ->where('id', '!=', $request->user()->id);

            if ($request->filled('search')) {
                $search = $request->get('search');
                $query->where(function ($q) use ($search) {
                    $q->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            }

            if ($request->filled('role')) {
                $query->whereHas('role', fn ($q) => $q->where('slug', $request->get('role')));
            }

            $users = $query->orderBy('first_name')
                ->limit(300)
                ->get(['id', 'uuid', 'first_name', 'last_name', 'avatar', 'agent_type', 'role_id'])
                ->map(function ($user) {
                    return [
                        'id' => $user->id,
                        'uuid' => $user->uuid,
                        'full_name' => trim($user->first_name . ' ' . $user->last_name),
                        'avatar' => $user->avatar,
                        'agent_type' => $user->agent_type,
                        'role' => $user->role ? ['slug' => $user->role->slug, 'name' => $user->role->name] : null,
                    ];
                });

            return response()->json(['success' => true, 'data' => $users]);
        } catch (\Exception $e) {
            Log::error('Messageable users list failed', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la recuperation des utilisateurs'
            ], 500);
        }
    }

    /**
     * Liste des messages de l'utilisateur
     */
    public function index(Request $request)
    {
        try {
            $query = Message::with(['sender.role', 'recipient.role', 'property'])
                ->forUser($request->user()->id);
            $this->applyListFilters($query, $request);
            $perPage = min((int) $request->get('per_page', 20), 100);
            $messages = $query->orderBy('created_at', 'desc')->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $messages
            ]);

        } catch (\Exception $e) {
            Log::error('Message index failed', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la recuperation'
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
            'subject' => 'nullable|string|max:' . self::SUBJECT_MAX_LENGTH,
            'message' => 'required|string|max:' . self::MESSAGE_MAX_LENGTH,
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        if ((int) $request->recipient_id === $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Vous ne pouvez pas vous envoyer un message a vous-meme.'
            ], 422);
        }

        try {
            $content = $this->sanitizeContent($request->message);
            $subject = $request->filled('subject')
                ? $this->sanitizeContent($request->subject)
                : $this->deriveSubject($content);

            $message = Message::create([
                'sender_id' => $request->user()->id,
                'recipient_id' => $request->recipient_id,
                'property_id' => $request->property_id,
                'subject' => $subject,
                'message' => $content,
            ]);

            try {
                Notification::create([
                    'user_id' => $request->recipient_id,
                    'type' => 'message_received',
                    'title' => 'Nouveau message',
                    'message' => "Vous avez recu un message de {$request->user()->full_name}",
                    'data' => json_encode(['message_uuid' => $message->uuid]),
                ]);
            } catch (\Throwable $e) {
                Log::error('Notification message_received failed', [
                    'error' => $e->getMessage(),
                    'message_uuid' => $message->uuid,
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Message envoye avec succes',
                'data' => $message
            ], 201);

        } catch (\Exception $e) {
            Log::error('Message send failed', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'envoi'
            ], 500);
        }
    }

    /**
     * Details d'un message + fil de discussion complet
     */
    public function show(Request $request, $uuid)
    {
        try {
            $message = Message::with(['sender.role', 'recipient.role', 'property'])
                ->where('uuid', $uuid)
                ->firstOrFail();

            if (!$this->assertParticipant($message, $request->user()?->id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Acces non autorise a ce message.'
                ], 403);
            }

            $thread = $this->markThreadAsRead($message->threadMessages(), $request->user()?->id);

            return response()->json([
                'success' => true,
                'data' => $message,
                'thread' => $thread,
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Message non trouve'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Message show failed', ['error' => $e->getMessage(), 'uuid' => $uuid]);
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la recuperation du message'
            ], 500);
        }
    }

    /**
     * Repondre a un message
     */
    public function reply(Request $request, $uuid)
    {
        $validator = Validator::make($request->all(), [
            'message' => 'required|string|max:' . self::MESSAGE_MAX_LENGTH,
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $parentMessage = Message::where('uuid', $uuid)->firstOrFail();

            if (!$this->assertParticipant($parentMessage, $request->user()?->id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Acces non autorise a ce message.'
                ], 403);
            }

            $reply = $this->createReplyMessage($request->user()->id, $parentMessage, $request->message);
            $this->notifyReply($reply, $request->user());

            return response()->json([
                'success' => true,
                'message' => 'Reponse envoyee',
                'data' => $reply
            ], 201);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Message non trouve'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Message reply failed', ['error' => $e->getMessage(), 'uuid' => $uuid]);
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la reponse'
            ], 500);
        }
    }

    /**
     * Messages pour les agents
     */
    public function agentMessages(Request $request)
    {
        try {
            $query = Message::with(['sender.role', 'recipient.role', 'property'])
                ->forUser($request->user()->id);
            $this->applyListFilters($query, $request);
            $perPage = min((int) $request->get('per_page', 20), 100);
            $messages = $query->orderBy('is_read', 'asc')
                ->orderBy('created_at', 'desc')
                ->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $messages
            ]);

        } catch (\Exception $e) {
            Log::error('Agent messages list failed', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la recuperation'
            ], 500);
        }
    }

    /**
     * Repondre a un message (agent)
     */
    public function respond(Request $request, $uuid)
    {
        $validator = Validator::make($request->all(), [
            'message' => 'required|string|max:' . self::MESSAGE_MAX_LENGTH,
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $parentMessage = Message::where('uuid', $uuid)->firstOrFail();

            if (!$this->assertParticipant($parentMessage, $request->user()?->id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Acces non autorise a ce message.'
                ], 403);
            }

            $reply = $this->createReplyMessage($request->user()->id, $parentMessage, $request->message);
            $this->notifyReply($reply, $request->user());

            return response()->json([
                'success' => true,
                'message' => 'Reponse envoyee',
                'data' => $reply
            ], 201);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Message non trouve'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Agent message respond failed', ['error' => $e->getMessage(), 'uuid' => $uuid]);
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la reponse'
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
                'message' => 'Message marque comme lu'
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Message non trouve'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Agent mark-read failed', ['error' => $e->getMessage(), 'uuid' => $uuid]);
            return response()->json([
                'success' => false,
                'message' => 'Erreur'
            ], 500);
        }
    }

    /**
     * Messages pour proprietaire
     */
    public function ownerMessages(Request $request)
    {
        try {
            $query = Message::with(['sender.role', 'recipient.role', 'property'])
                ->forUser($request->user()->id);
            $this->applyListFilters($query, $request);
            $perPage = min((int) $request->get('per_page', 20), 100);
            $messages = $query->orderBy('is_read', 'asc')
                ->orderBy('created_at', 'desc')
                ->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $messages
            ]);
        } catch (\Exception $e) {
            Log::error('Owner messages list failed', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la recuperation'
            ], 500);
        }
    }

    public function ownerShow(Request $request, $uuid)
    {
        try {
            $message = Message::with(['sender.role', 'recipient.role', 'property'])
                ->where('uuid', $uuid)
                ->firstOrFail();

            if (!$this->assertParticipant($message, $request->user()?->id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Acces non autorise a ce message.'
                ], 403);
            }

            $thread = $this->markThreadAsRead($message->threadMessages(), $request->user()?->id);

            return response()->json([
                'success' => true,
                'data' => $message,
                'thread' => $thread,
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Message non trouve'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Owner message show failed', ['error' => $e->getMessage(), 'uuid' => $uuid]);
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la recuperation du message'
            ], 500);
        }
    }

    public function ownerReply(Request $request, $uuid)
    {
        $validator = Validator::make($request->all(), [
            'message' => 'required|string|max:' . self::MESSAGE_MAX_LENGTH,
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $parentMessage = Message::where('uuid', $uuid)->firstOrFail();

            if (!$this->assertParticipant($parentMessage, $request->user()?->id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Acces non autorise a ce message.'
                ], 403);
            }

            $reply = $this->createReplyMessage($request->user()->id, $parentMessage, $request->message);
            $this->notifyReply($reply, $request->user());

            return response()->json([
                'success' => true,
                'message' => 'Reponse envoyee',
                'data' => $reply
            ], 201);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Message non trouve'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Owner message reply failed', ['error' => $e->getMessage(), 'uuid' => $uuid]);
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la reponse'
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
                'message' => 'Message marque comme lu'
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Message non trouve'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Owner mark-read failed', ['error' => $e->getMessage(), 'uuid' => $uuid]);
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
                'message' => 'Message supprime'
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Message non trouve'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Owner delete failed', ['error' => $e->getMessage(), 'uuid' => $uuid]);
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
            $query = Message::with(['sender.role', 'recipient.role', 'property']);
            $this->applyListFilters($query, $request);
            $this->applyArchiveFilter($query, $request);
            $perPage = min((int) $request->get('per_page', 20), 100);
            $messages = $query->orderBy('created_at', 'desc')->paginate($perPage);

            $stats = [
                'total' => Message::count(),
                'unread' => Message::where('is_read', false)->count(),
                'replied' => Message::whereNotNull('parent_message_id')->count(),
                'conversations' => Message::whereNull('parent_message_id')->count(),
                'archived' => Message::whereNull('parent_message_id')->whereNotNull('archived_at')->count(),
            ];

            return response()->json([
                'success' => true,
                'data' => $messages,
                'stats' => $stats,
            ]);
        } catch (\Exception $e) {
            Log::error('Admin messages list failed', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la recuperation'
            ], 500);
        }
    }

    public function adminShow(Request $request, $uuid)
    {
        try {
            $message = Message::with(['sender.role', 'recipient.role', 'property'])
                ->where('uuid', $uuid)
                ->firstOrFail();

            $thread = $this->markThreadAsRead($message->threadMessages(), $request->user()?->id);

            return response()->json([
                'success' => true,
                'data' => $message,
                'thread' => $thread,
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Message non trouve'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Admin message show failed', ['error' => $e->getMessage(), 'uuid' => $uuid]);
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la recuperation du message'
            ], 500);
        }
    }

    public function adminCreate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'recipient_id' => 'required|exists:users,id',
            'property_id' => 'nullable|exists:properties,id',
            'subject' => 'nullable|string|max:' . self::SUBJECT_MAX_LENGTH,
            'message' => 'required|string|max:' . self::MESSAGE_MAX_LENGTH,
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        if ((int) $request->recipient_id === $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Vous ne pouvez pas vous envoyer un message a vous-meme.'
            ], 422);
        }

        try {
            $content = $this->sanitizeContent($request->message);
            $subject = $request->filled('subject')
                ? $this->sanitizeContent($request->subject)
                : $this->deriveSubject($content);

            $message = Message::create([
                'sender_id' => $request->user()->id,
                'recipient_id' => $request->recipient_id,
                'property_id' => $request->property_id,
                'subject' => $subject,
                'message' => $content,
            ]);

            try {
                Notification::create([
                    'user_id' => $request->recipient_id,
                    'type' => 'message_received',
                    'title' => 'Nouveau message',
                    'message' => "Vous avez recu un message de l'equipe {$request->user()->full_name}",
                    'data' => json_encode(['message_uuid' => $message->uuid]),
                ]);
            } catch (\Throwable $e) {
                Log::error('Admin notification message_received failed', [
                    'error' => $e->getMessage(),
                    'message_uuid' => $message->uuid,
                ]);
            }

            return response()->json([
                'success' => true,
                'data' => $message
            ], 201);
        } catch (\Exception $e) {
            Log::error('Admin message create failed', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la creation'
            ], 500);
        }
    }

    public function adminUpdate(Request $request, $uuid)
    {
        $validator = Validator::make($request->all(), [
            'subject' => 'sometimes|string|max:' . self::SUBJECT_MAX_LENGTH,
            'message' => 'sometimes|string|max:' . self::MESSAGE_MAX_LENGTH,
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $message = Message::where('uuid', $uuid)->firstOrFail();
            $payload = $request->only(['subject', 'message']);
            foreach ($payload as $key => $value) {
                $payload[$key] = $this->sanitizeContent($value);
            }
            $message->update($payload);

            return response()->json([
                'success' => true,
                'data' => $message
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Message non trouve'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Admin message update failed', ['error' => $e->getMessage(), 'uuid' => $uuid]);
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
                'message' => 'Message supprime'
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Message non trouve'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Admin message delete failed', ['error' => $e->getMessage(), 'uuid' => $uuid]);
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
                'message' => 'Message marque comme lu'
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Message non trouve'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Admin mark-read failed', ['error' => $e->getMessage(), 'uuid' => $uuid]);
            return response()->json([
                'success' => false,
                'message' => 'Erreur'
            ], 500);
        }
    }

    /**
     * Archive toute la conversation (racine + reponses) a laquelle appartient
     * le message vise, en marquant uniquement le message racine.
     */
    public function adminArchive($uuid)
    {
        try {
            $message = Message::where('uuid', $uuid)->firstOrFail();
            $root = $message->threadRoot();
            $root->update(['archived_at' => now()]);

            return response()->json([
                'success' => true,
                'message' => 'Conversation archivee'
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Message non trouve'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Admin message archive failed', ['error' => $e->getMessage(), 'uuid' => $uuid]);
            return response()->json([
                'success' => false,
                'message' => "Erreur lors de l'archivage"
            ], 500);
        }
    }

    public function adminUnarchive($uuid)
    {
        try {
            $message = Message::where('uuid', $uuid)->firstOrFail();
            $root = $message->threadRoot();
            $root->update(['archived_at' => null]);

            return response()->json([
                'success' => true,
                'message' => 'Conversation desarchivee'
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Message non trouve'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Admin message unarchive failed', ['error' => $e->getMessage(), 'uuid' => $uuid]);
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du desarchivage'
            ], 500);
        }
    }

    public function adminReply(Request $request, $uuid)
    {
        $validator = Validator::make($request->all(), [
            'message' => 'required|string|max:' . self::MESSAGE_MAX_LENGTH,
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
            $this->notifyReply($reply, $request->user());

            return response()->json([
                'success' => true,
                'message' => 'Reponse envoyee',
                'data' => $reply
            ], 201);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Message non trouve'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Admin message reply failed', ['error' => $e->getMessage(), 'uuid' => $uuid]);
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la reponse'
            ], 500);
        }
    }
}
