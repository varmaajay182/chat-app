<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class MessageHandelEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public $chatData;
    public $sender;
    public $oldData;
    public function __construct($chatData,$sender,$oldData)
    {
       
        //  Log::info('Chat Data:', ['data' => $chatData]);
        $this->chatData = $chatData;
        $this->sender = $sender;
        $this->oldData = $oldData;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('message-handel'),
        ];
    }
}
