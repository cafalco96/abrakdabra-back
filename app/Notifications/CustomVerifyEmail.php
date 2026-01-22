<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail as BaseVerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;

class CustomVerifyEmail extends BaseVerifyEmail
{
    public function toMail($notifiable): MailMessage
    {
        $url = $this->verificationUrl($notifiable);

        return (new MailMessage)
            ->subject('Confirma tu correo en Abrakdabra')
            ->greeting('¡Bienvenido, '.$notifiable->name.'!')
            ->line('Gracias por registrarte. Confirma tu correo para activar tu cuenta.')
            ->action('Verificar correo', $url)
            ->line('Si no creaste esta cuenta, ignora este mensaje.');
    }
}
