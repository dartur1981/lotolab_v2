<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DezenaSelecionadaEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $bolaoId;
    public int $userId;
    public string $userName;
    public array $minhasDezenas;

    public function __construct(int $bolaoId, int $userId, string $userName, array $minhasDezenas)
    {
        $this->bolaoId = $bolaoId;
        $this->userId = $userId;
        $this->userName = $userName;
        $this->minhasDezenas = $minhasDezenas;
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('bolao.' . $this->bolaoId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'DezenaSelecionadaEvent';
    }
}
