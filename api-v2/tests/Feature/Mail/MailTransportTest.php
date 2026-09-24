<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Mail\Markdown;
use Illuminate\Support\Facades\Mail;

it('resolves the resend mail transport', function () {
    config()->set('services.resend.key', 're_test_key');

    $transport = Mail::mailer('resend')->getSymfonyTransport();

    expect(strtolower(class_basename($transport)))->toContain('resend');
});

it('renders notification emails with the kennelo theme', function () {
    $user = User::factory()->create();

    $message = (new VerifyEmail)->toMail($user);
    $html = (string) app(Markdown::class)->render('notifications::email', $message->data());

    expect($html)->toContain('#cbef6a')->toContain('#131613');
});

it('translates the verification email into french', function () {
    $user = User::factory()->create();

    app()->setLocale('fr');
    $message = (new VerifyEmail)->toMail($user);
    $html = (string) app(Markdown::class)->render('notifications::email', $message->data());

    expect($html)->toContain('Vérifier')->toContain('Cordialement,');
});
