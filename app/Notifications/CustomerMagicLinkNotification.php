<?php

namespace App\Notifications;

use DateTimeInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CustomerMagicLinkNotification extends Notification implements ShouldBeEncrypted, ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly string $token,
        public readonly DateTimeInterface $expiresAt,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Seu link de acesso')
            ->greeting('Olá!')
            ->line('Recebemos uma solicitação para acessar o atendimento.')
            ->action('Acessar atendimento', route('customer.access.consume', ['token' => $this->token]))
            ->line('Este link é de uso único e expira em '.$this->expiresAt->format('d/m/Y H:i').'.')
            ->line('Se você não fez esta solicitação, ignore esta mensagem.');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [];
    }
}
