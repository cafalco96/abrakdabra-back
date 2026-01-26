<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword as BaseResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

class CustomResetPassword extends BaseResetPassword
{
    public function toMail($notifiable): MailMessage
    {
        $url = $this->resetUrl($notifiable);

        return (new MailMessage)
            ->subject('Recupera tu contraseña de Abrakdabra')
            ->view('emails.auth.reset-password', [
                'user' => $notifiable,
                'url'  => $url,
            ]);
    }
}
