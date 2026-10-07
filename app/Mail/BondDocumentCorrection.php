<?php

namespace app\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

use Illuminate\Mail\Mailables\Attachment;

use app\Models\Client;

class BondDocumentCorrection extends Mailable
{
    use Queueable, SerializesModels;

    private $client;
    private $bondFilePath;
    private $receiptFilePath;

    /**
     * Create a new message instance.
     */
    public function __construct(Client $client, $bondFilePath, $receiptFilePath = null)
    {
        $this->client = $client;
        $this->bondFilePath = $bondFilePath;
        $this->receiptFilePath = $receiptFilePath;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Corrected Document: Participation Agreement of Subscription',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.bond_document_correction',
            with: [
                "client" => $this->client
            ]
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        $attachments = [
            $this->attachmentFor($this->bondFilePath, "Participation_Agreement_of_Subscription.pdf")
        ];

        if ($this->receiptFilePath) {
            $attachments[] = $this->attachmentFor($this->receiptFilePath, "Receipt.pdf");
        }

        return $attachments;
    }

    private function attachmentFor($filePath, $asName)
    {
        if (str_starts_with($filePath, 'http')) {
            return Attachment::fromData(fn () => file_get_contents($filePath), $asName);
        }

        return Attachment::fromPath(public_path($filePath))->as($asName);
    }
}
