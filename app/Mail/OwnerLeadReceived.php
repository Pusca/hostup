<?php

namespace App\Mail;

use App\Models\OwnerLead;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OwnerLeadReceived extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public OwnerLead $lead)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Nuova richiesta proprietario — ' . $this->lead->name,
            replyTo: [new Address($this->lead->email, $this->lead->name)],
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.owner-lead');
    }
}
