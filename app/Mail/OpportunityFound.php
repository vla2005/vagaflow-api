<?php

namespace App\Mail;

use App\Models\Job;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OpportunityFound extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Job $job) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Nova vaga adequada: '.$this->job->title);
    }

    public function content(): Content
    {
        return new Content(view: 'mail.opportunity');
    }

    public function attachments(): array
    {
        return [];
    }
}
