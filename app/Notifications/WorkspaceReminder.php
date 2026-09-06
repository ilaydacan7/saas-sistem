<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Tenant;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;

class WorkspaceReminder extends Notification
{
    /**
     * @param  Collection<int, Tenant>  $tenants
     */
    public function __construct(
        private readonly Collection $tenants,
        private readonly string $port = '',
        private readonly string $scheme = 'http',
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mesaj = (new MailMessage)
            ->subject('Şirket adresleriniz')
            ->greeting('Merhaba,')
            ->line($this->tenants->count() === 1
                ? 'Bu e-posta adresi aşağıdaki şirket hesabına kayıtlı:'
                : 'Bu e-posta adresi aşağıdaki şirket hesaplarına kayıtlı:');

        foreach ($this->tenants as $tenant) {
            $mesaj->line('• '.$tenant->name.' — '.$tenant->host());
        }

        $ilk = $this->tenants->first();

        return $mesaj
            ->action('Giriş yap', $this->scheme.'://'.$ilk->host().$this->port.'/giris')
            ->line('Bu isteği siz yapmadıysanız bu e-postayı yok sayabilirsiniz.')
            ->salutation('Sevgiler, '.config('app.name'));
    }
}
