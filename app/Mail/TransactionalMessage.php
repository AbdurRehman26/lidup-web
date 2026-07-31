<?php

namespace App\Mail;

use App\Data\TransactionalEmail;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TransactionalMessage extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public readonly TransactionalEmail $email) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            to: $this->email->to,
            replyTo: $this->email->replyTo,
            subject: $this->email->subject,
        );
    }

    public function content(): Content
    {
        return new Content(htmlString: $this->email->html);
    }
}
