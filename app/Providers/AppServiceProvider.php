<?php

declare(strict_types=1);

namespace App\Providers;

use App\Auth\TenantAwarePasswordBrokerManager;
use App\Tenancy\TenantContext;
use App\Tenancy\TenantResolverManager;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Passwords\PasswordResetServiceProvider;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(TenantContext::class);
        $this->app->singleton(TenantResolverManager::class);

        $this->app->register(PasswordResetServiceProvider::class);
        $this->app->singleton('auth.password', fn ($app) => new TenantAwarePasswordBrokerManager($app));
    }

    public function boot(): void
    {
        Model::shouldBeStrict(! $this->app->isProduction());

        if ($this->app->isProduction()) {
            URL::forceScheme('https');
        }

        ResetPassword::toMailUsing(function (object $notifiable, string $token): MailMessage {
            $adres = url(route('parola.sifirla', [
                'token' => $token,
                'email' => $notifiable->getEmailForPasswordReset(),
            ], false));

            $dakika = config('auth.passwords.'.config('auth.defaults.passwords').'.expire');

            return (new MailMessage)
                ->subject('Parola sıfırlama')
                ->greeting('Merhaba,')
                ->line('Parolanızı sıfırlamak için bir istek aldık. Aşağıdaki bağlantıdan yeni parolanızı belirleyebilirsiniz.')
                ->action('Parolamı sıfırla', $adres)
                ->line("Bu bağlantı {$dakika} dakika sonra geçersiz olur.")
                ->line('Bu isteği siz yapmadıysanız yapmanız gereken bir şey yok, parolanız değişmez.')
                ->salutation('Sevgiler, '.config('app.name'));
        });
    }
}
