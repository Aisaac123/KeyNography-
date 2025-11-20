<?php

namespace App\Events;

use App\Models\User;
use App\Models\ChatMessage;
use Illuminate\Broadcasting\Channel; // ✅ Channel público
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class GlobalChatMessage implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public ChatMessage $message,
        public User $user
    ) {}

    public function broadcastOn(): Channel|array
    {
        return new PrivateChannel('global-chat'); // ✅ Canal PÚBLICO
    }

    public function broadcastAs(): string
    {
        return 'new-global-message';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->message->id,
            'message' => $this->message->message,
            'user' => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'email' => $this->user->email,
            ],
            'timestamp' => $this->message->created_at->toIso8601String(),
            'human_time' => $this->message->created_at->diffForHumans(),
        ];
    }
}
