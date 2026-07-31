<?php

namespace Tests\Feature;

use App\Contracts\TransactionalEmailSender;
use App\Data\TransactionalEmail;
use App\Mail\TransactionalMessage;
use Illuminate\Contracts\Mail\Mailer;
use Mockery;
use Tests\TestCase;

class TransactionalEmailSenderTest extends TestCase
{
    public function test_the_configured_transactional_email_sender_uses_laravel_mail(): void
    {
        $email = new TransactionalEmail(
            to: ['customer@example.com'],
            subject: 'LidUp email test',
            html: '<p>Your email is ready.</p>',
            replyTo: ['support@lidup.app'],
        );

        $mailer = Mockery::mock(Mailer::class);
        $mailer->shouldReceive('send')
            ->once()
            ->with(Mockery::on(function (object $message) use ($email): bool {
                return $message instanceof TransactionalMessage
                    && $message->email === $email;
            }));

        $this->app->instance(Mailer::class, $mailer);

        app(TransactionalEmailSender::class)->send($email);

        $envelope = (new TransactionalMessage($email))->envelope();

        $this->assertSame('LidUp email test', $envelope->subject);
        $this->assertSame('customer@example.com', $envelope->to[0]->address);
        $this->assertSame('support@lidup.app', $envelope->replyTo[0]->address);
    }
}
