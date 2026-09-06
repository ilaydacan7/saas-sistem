<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Invitation;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TeamInvitation extends Notification
{
    public function __construct(private readonly Invitation $invitation) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $tenant = $this->invitation->tenant;
        $davetEden = $this->invitation->invitedBy?->name;

        $adres = url(route('davet.kabul', ['token' => $this->invitation->token], false));

        return (new MailMessage)
            ->subject($tenant->name.' ekibine davet edildiniz')
            ->greeting('Merhaba,')
            ->line($davetEden !== null
                ? "{$davetEden}, sizi {$tenant->name} ekibine katılmaya davet etti."
                : "{$tenant->name} ekibine katılmaya davet edildiniz.")
            ->line('Rolünüz: '.$this->invitation->role->label().'.')
            ->action('Daveti kabul et', $adres)
            ->line('Bu bağlantı '.$this->invitation->expires_at->format('d.m.Y H:i').' tarihine kadar geçerlidir.')
            ->line('Bu daveti beklemiyorsanız bu e-postayı yok sayabilirsiniz.')
            ->salutation('Sevgiler, '.config('app.name'));
    }
}
