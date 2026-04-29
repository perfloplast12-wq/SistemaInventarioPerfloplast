<?php

namespace App\Events;

use App\Models\DispatchLocation;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LocationUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $location;
    public $isOffline;

    /**
     * Create a new event instance.
     */
    public function __construct(DispatchLocation $location, bool $isOffline = false)
    {
        $this->location = $location;
        $this->isOffline = $isOffline;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('dispatch.' . $this->location->dispatch_id),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'location.updated';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'lat' => $this->location->lat,
            'lng' => $this->location->lng,
            'speed' => $this->location->speed,
            'heading' => $this->location->heading,
            'timestamp' => $this->location->created_at->toIso8601String(),
            'is_offline' => $this->isOffline,
        ];
    }
}
