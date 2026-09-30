<?php

namespace App\Mail\Dhp;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * Backup status mail sent through the dedicated backup mailer.
 * Attaches only the encrypted archive; the message itself reveals
 * no filenames, paths, passwords or database detail.
 */
class BackupArchiveMail extends Mailable implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly bool $attached,
        public readonly ?string $archivePath = null,
        public readonly ?string $archiveName = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Digital Health Passport backup status',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.dhp.backup-status',
            with: ['attached' => $this->attached],
        );
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        if (! $this->attached || $this->archivePath === null || $this->archiveName === null) {
            return [];
        }

        return [Attachment::fromPath($this->archivePath)->as($this->archiveName)];
    }
}
