<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Message extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'uuid', 'sender_id', 'recipient_id', 'property_id',
        'subject', 'message', 'is_read', 'read_at', 'parent_message_id', 'archived_at'
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'read_at' => 'datetime',
        'archived_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function recipient()
    {
        return $this->belongsTo(User::class, 'recipient_id');
    }

    public function property()
    {
        return $this->belongsTo(Property::class);
    }

    public function parentMessage()
    {
        return $this->belongsTo(Message::class, 'parent_message_id');
    }

    public function replies()
    {
        return $this->hasMany(Message::class, 'parent_message_id');
    }

    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where(function ($q) use ($userId) {
            $q->where('sender_id', $userId)
              ->orWhere('recipient_id', $userId);
        });
    }

    public function markAsRead()
    {
        if (!$this->is_read) {
            $this->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
        }
    }

    /**
     * Vrai si l'utilisateur est expediteur ou destinataire de ce message precis.
     */
    public function isParticipant(?int $userId): bool
    {
        if (!$userId) {
            return false;
        }
        return $this->sender_id === $userId || $this->recipient_id === $userId;
    }

    /**
     * Remonte la chaine de reponses jusqu'au message racine de la conversation.
     * Protege contre une boucle (donnees corrompues) via un compteur de garde.
     */
    public function threadRoot(): Message
    {
        $node = $this;
        $visited = 0;
        while ($node->parent_message_id !== null && $visited < 100) {
            $parent = static::withTrashed()->find($node->parent_message_id);
            if (!$parent || $parent->id === $node->id) {
                break;
            }
            $node = $parent;
            $visited++;
        }
        return $node;
    }

    /**
     * Retourne tous les messages de la conversation (racine + toutes les
     * reponses, quel que soit leur niveau de profondeur), triees par date.
     * Fonctionne aussi bien avec l'ancien modele imbrique qu'avec le nouveau
     * modele "toutes les reponses pointent vers la racine".
     */
    public function threadMessages()
    {
        $root = $this->threadRoot();
        $all = collect([$root]);
        $frontierIds = collect([$root->id]);
        $guard = 0;

        while ($frontierIds->isNotEmpty() && $guard < 100) {
            $children = static::with(['sender.role', 'recipient.role'])
                ->whereIn('parent_message_id', $frontierIds)
                ->get();
            if ($children->isEmpty()) {
                break;
            }
            $all = $all->merge($children);
            $frontierIds = $children->pluck('id');
            $guard++;
        }

        return $all->sortBy('created_at')->values();
    }

    /**
     * Vrai si l'utilisateur participe a au moins un message de la conversation
     * complete (utile pour autoriser l'acces a un fil dont l'utilisateur n'a
     * pas ecrit le tout premier message, par ex. une reponse d'un tiers).
     */
    public function threadParticipantIds()
    {
        return $this->threadMessages()
            ->flatMap(fn ($message) => [$message->sender_id, $message->recipient_id])
            ->unique()
            ->values();
    }
}
