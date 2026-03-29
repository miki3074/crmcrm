<?php

namespace App\Events;

use App\Models\FlutterMessage;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast; // Важно!
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class FlutterMessageSent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $flutterMessage;

    public function __construct(FlutterMessage $flutterMessage)
    {
        $this->flutterMessage = $flutterMessage->load('user:id,name');
    }

    public function broadcastOn(): array
    {
        return [new Channel('flutter-chat')];
    }

    public function broadcastAs(): string
    {
        return 'message.sent';
    }
}
