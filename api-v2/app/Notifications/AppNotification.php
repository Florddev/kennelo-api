<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Enums\NotificationTypeEnum;
use App\Models\User;
use App\Services\Conversation\ConversationService;
use Carbon\CarbonImmutable;
use DateTimeInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class AppNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @param  array<string, mixed>  $data
     */
    public function __construct(
        private NotificationTypeEnum $type,
        private array $data = []
    ) {
        $this->afterCommit();
    }

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return array_merge(['user_database'], $this->type->sendsEmail() ? ['mail'] : []);
    }

    public function withDelay(object $notifiable, string $channel): ?DateTimeInterface
    {
        return $channel === 'mail' && $this->type === NotificationTypeEnum::NEW_MESSAGE
            ? now()->addMinutes((int) config('notifications.message_email_delay_minutes'))
            : null;
    }

    public function shouldSend(object $notifiable, string $channel): bool
    {
        if ($channel !== 'mail' || $this->type !== NotificationTypeEnum::NEW_MESSAGE || ! $notifiable instanceof User) {
            return true;
        }

        return app(ConversationService::class)->isLatestUnread((string) ($this->data['message_id'] ?? ''), $notifiable);
    }

    /** @return array{type: string, data: array<string, mixed>} */
    public function toUserDatabase(object $notifiable): array
    {
        return [
            'type' => $this->type->value,
            'data' => $this->data,
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $key = 'notification-emails.'.$this->type->value;
        $params = [
            'activity' => (string) ($this->data['activity_name'] ?? ''),
            'organization' => (string) ($this->data['organization_name'] ?? ''),
            'amount' => (string) ($this->data['amount'] ?? ''),
            'sender' => (string) ($this->data['sender_name'] ?? ''),
            'preview' => (string) ($this->data['content_preview'] ?? ''),
            'date' => $this->date($this->data['due_by'] ?? $this->data['expires_at'] ?? null),
        ];

        $mail = (new MailMessage)
            ->subject(__($key.'.subject', $params))
            ->greeting(__('notification-emails.greeting'))
            ->line(__($key.'.line', $params));

        if (filled($this->data['reason'] ?? null)) {
            $mail->line(__('notification-emails.reason', ['reason' => (string) $this->data['reason']]));
        }

        if (filled($this->data['banned_until'] ?? null)) {
            $mail->line(__('notification-emails.until', ['date' => $this->date($this->data['banned_until'])]));
        }

        $link = $this->type->mailLink();
        $url = $link === null ? null : $this->url((string) config('notifications.links.'.$link));

        if ($link !== null && $url !== null) {
            $mail->action(__('notification-emails.actions.'.$link), $url);
        }

        return $mail;
    }

    private function url(string $template): ?string
    {
        $replacements = [
            ':frontend' => rtrim((string) config('app.frontend_url'), '/'),
            ':back_office' => rtrim((string) config('app.back_office_url'), '/'),
        ];

        foreach ($this->data as $name => $value) {
            if (is_string($value) || is_int($value)) {
                $replacements[':'.$name] = rawurlencode((string) $value);
            }
        }

        $url = strtr($template, $replacements);

        return preg_match('/:[a-z_]+/', $url) === 1 ? null : $url;
    }

    private function date(mixed $value): string
    {
        return is_string($value) && $value !== ''
            ? CarbonImmutable::parse($value)->locale(app()->getLocale())->isoFormat('LL')
            : '';
    }
}
