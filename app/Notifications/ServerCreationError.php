<?php

namespace App\Notifications;

use App\Models\Server;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ServerCreationError extends Notification

{
    use Queueable;
    /**
     * @var Server
     */
    private $server;

    /**
     * Create a new notification instance.
     *
     * @param Server $server
     */
    public function __construct(Server $server)
    {
        $this->server = $server;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param mixed $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @param mixed $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            'title' => __("Chyba při vytváření serveru"),
            'content' => "
                <p>Dobrý den <strong>{$this->server->User->name}</strong>, nastala neočekávaná chyba...</p>
                <p>Nasrala chyba při vytváření serveru na našem panelu. Pokud čtete tuto zprávu, kontaktujte prosím majitele.</p>
                <p>Omlouváme se za potíže.</p>
                <p>".config('app.name', 'Laravel')."</p>
            ",
        ];
    }
}
