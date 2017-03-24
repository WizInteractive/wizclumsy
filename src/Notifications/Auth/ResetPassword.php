<?php

namespace Wizclumsy\CMS\Notifications\Auth;

use Wizclumsy\CMS\Notifications\MailMessage;
use Illuminate\Auth\Notifications\ResetPassword as BaseResetPassword;

class ResetPassword extends BaseResetPassword
{
    /**
     * The view for the message.
     *
     * @var string
     */
    public $view = 'clumsy::notifications.email';

    /**
     * Build the mail representation of the notification.
     *
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject(trans('clumsy::titles.reset-password'))
            ->line([trans('clumsy::emails.content.reset-password')])
            ->action(trans('clumsy::titles.reset-password'), route('clumsy.do-reset-password', $this->token))
            ->line(trans('clumsy::emails.content.ignore-reset-password'));
    }
}
