<?php

namespace App\Notifications;

use App\Models\Configuration;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ServersSuspendedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail' , 'database'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        return (new MailMessage)
                    ->subject('Vaše servery byly pozastaveny!')
                    ->greeting('Vaše servery byly pozastaveny!')
                    ->line("Pro automatické obnovení serverů je potřeba zakoupit další kredity.")
                    ->action('Zakoupit kredity', route('store.index'))
                    ->line('Pokud máte nějaké dotazy, ozvěte se.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            'title'   => "Servery pozastaveny!",
            'content' => "
                <h5>Vaše servery byly pozastaveny!</h5>
                <p>Pro automatické obnovení serverů je potřeba zakoupit další kredity.</p>
                <p>Pokud máte nějaké dotazy, ozvěte se.</p>
                <p>S přáním hezkého dne,<br />" . config('app.name', 'Laravel') . "</p>
            ",
        ];
    }
}
