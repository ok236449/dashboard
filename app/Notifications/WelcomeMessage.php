<?php

namespace App\Notifications;

use App\Models\Configuration;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class WelcomeMessage extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @var User
     */
    private $user;

    /**
     * Create a new notification instance.
     *
     * @param User $user
     */
    public function __construct(User $user)
    {
        $this->user = $user;
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
    public function AdditionalLines() 
        {
            $AdditionalLine = "";
            if(Configuration::getValueByKey('CREDITS_REWARD_AFTER_VERIFY_EMAIL') != 0) {
                $AdditionalLine .= "Ověřením emailu získáte ".Configuration::getValueByKey('CREDITS_REWARD_AFTER_VERIFY_EMAIL')." additional " . Configuration::getValueByKey('CREDITS_DISPLAY_NAME') . " <br />";
            }
            if(Configuration::getValueByKey('SERVER_LIMIT_REWARD_AFTER_VERIFY_EMAIL') != 0) {
                $AdditionalLine .= "Ověřením emailu také zvýšíte možný počet serverů o " . Configuration::getValueByKey('SERVER_LIMIT_REWARD_AFTER_VERIFY_EMAIL') . " <br />";
            }
            $AdditionalLine .="<br />";
            if(Configuration::getValueByKey('CREDITS_REWARD_AFTER_VERIFY_DISCORD') != 0) {
                $AdditionalLine .=  "Ověřením discordu získáte " . Configuration::getValueByKey('CREDITS_REWARD_AFTER_VERIFY_DISCORD') . " " . Configuration::getValueByKey('CREDITS_DISPLAY_NAME') . " a roli \"Ověřený\" na našem discord serveru s přístupem do dalších kanálů.<br />";
            }
            if(Configuration::getValueByKey('SERVER_LIMIT_REWARD_AFTER_VERIFY_DISCORD') != 0) {
                $AdditionalLine .=  "Ověřením discordu také zvýšíte možný počet serverů o " . Configuration::getValueByKey('SERVER_LIMIT_REWARD_AFTER_VERIFY_DISCORD') . " <br />";
            }

            return $AdditionalLine;
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
            'title'   => "Začínáme!",
            'content' => "
               <p>Dobrý den <strong>{$this->user->name}</strong>, vítejte na našem storu</p>
                <h5>Verifikace</h5>
                <p>Prosím ověřte si emailovou adresu. Dostanete na ni upozornění v případě nedostatku kreditů.</p>
                <p>
                  ".WelcomeMessage::AdditionalLines()."
                </p>
                <h5>Informace</h5>
                <p>Tento web můžete používat na správu vašich serverů.<br /> Tyto servery tu můžete vytvářet a mazat, do konzole serveru se dostanete použitím našeho panelu.<br /> Pokud máte jakýkoliv dotaz, kontaktujte nás prosím.</p>
                <p>Doufáme, že budete spokojeni s naším hostingem. Máte li nějaké návrhy na zlepšení, nebojte se dát vědět.</p>
                <p>S přáním hezkého dne,<br />" . config('app.name', 'Laravel') . "</p>
            ",
        ];
    }
}
