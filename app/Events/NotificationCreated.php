<?php

namespace App\Events;

use App\Models\Notification;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Support\Facades\Config;
use Illuminate\Queue\SerializesModels;

class NotificationCreated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Notification $notification;

    public function __construct(Notification $notification)
    {
        $this->notification = $notification;
    }

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('notifications.' . $this->notification->user_id);
    }

    public function broadcastAs(): string
    {
        return 'notification.created';
    }


    public function broadcastWhen(): bool
    {
        $default = Config::get('broadcasting.default');

        if ($default !== 'pusher') {
            return true;
        }

        $key = Config::get('broadcasting.connections.pusher.key');
        $secret = Config::get('broadcasting.connections.pusher.secret');
        $appId = Config::get('broadcasting.connections.pusher.app_id');
        $host = Config::get('broadcasting.connections.pusher.options.host');

        return filled($key) && filled($secret) && filled($appId) && filled($host);
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->notification->id,
            'user_id' => $this->notification->user_id,
            'type' => $this->notification->type,
            'title' => $this->notification->title,
            'message' => $this->notification->message,
            'data' => $this->notification->data,
            'is_read' => $this->notification->is_read,
            'read_at' => $this->notification->read_at,
            'created_at' => $this->notification->created_at,
        ];
    }
}
