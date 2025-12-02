<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\URL;

class TenantResetPasswordNotification extends ResetPassword
{
    protected string $tenant;

    public function __construct(string $token, string $tenant)
    {
        parent::__construct($token);
        $this->tenant = $tenant;
    }

    protected function resetUrl($notifiable)
    {
        return URL::route('password.reset', [
            'tenant' => $this->tenant,
            'token'  => $this->token,
            'email'  => $notifiable->getEmailForPasswordReset(),
        ], true);
    }
}
