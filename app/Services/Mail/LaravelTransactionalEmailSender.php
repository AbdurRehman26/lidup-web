<?php

namespace App\Services\Mail;

use App\Contracts\TransactionalEmailSender;
use App\Data\TransactionalEmail;
use App\Mail\TransactionalMessage;
use Illuminate\Contracts\Mail\Mailer;

final readonly class LaravelTransactionalEmailSender implements TransactionalEmailSender
{
    public function __construct(private Mailer $mailer) {}

    public function send(TransactionalEmail $email): void
    {
        $this->mailer->send(new TransactionalMessage($email));
    }
}
