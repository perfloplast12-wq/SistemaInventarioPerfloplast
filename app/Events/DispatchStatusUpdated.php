<?php
namespace App\Events;

use App\Models\Dispatch;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Evento que se dispara cuando el estado de un despacho cambia.
 * 
 * NOTA: Se removió ShouldBroadcast porque el servidor de WebSockets
 * (Pusher/Reverb) no está configurado en producción, lo cual causaba
 * errores 500 al intentar transmitir. Cuando se configure un servicio
 * de broadcasting, se puede volver a agregar `implements ShouldBroadcast`.
 */
class DispatchStatusUpdated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $dispatchId;
    public $status;

    /**
     * Create a new event instance.
     */
    public function __construct(Dispatch $dispatch)
    {
        $this->dispatchId = $dispatch->id;
        $this->status = $dispatch->status;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('dispatch.' . $this->dispatchId),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'status.updated';
    }
}
