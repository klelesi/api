<?php

namespace App\Notifications;

use App\Services\FrontendAppHelper;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WelcomeNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(private FrontendAppHelper $helper)
    {
        //
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->from('miha@klele.si')
            ->subject("klele.si te pozdravlja!")
            ->greeting('Hej!')
            ->line("Sem Miha in zelo me veseli, da smo te končno prepričali, da postaneš del naše skupnosti.")
            ->line("Vedno bo prostor zate, ne glede na to, ali le nemo opazuješ ali pa aktivno sodeluješ s komentarji in prispevki.")
            ->line("Piši mi, če kaj potrebuješ.")
            ->action('klele.si', $this->helper->getLoginFormUrl())
            ->salutation("Vse najlepše, Miha.");
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
