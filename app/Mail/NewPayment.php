<?php

namespace app\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

use Illuminate\Mail\Mailables\Attachment;

use app\Models\Payment;

class NewPayment extends Mailable
{
    use Queueable, SerializesModels;

    private $filePath;
    private $payment;

    /**
     * Create a new message instance.
     */
    public function __construct(Payment $payment, $filePath)
    {
        $this->payment = $payment;
        $this->filePath = $filePath;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'New Payment',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.new_payment',
            with: [
                'payment' => $this->payment
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
        if (str_starts_with($this->filePath, 'http')) {
            return [
                Attachment::fromData(fn () => file_get_contents($this->filePath), "Receipt.pdf")
            ];
        }


        return [
            Attachment::fromPath(public_path($this->filePath))
                ->as("Receipt.pdf") // Optional: specify the name in the email
        ];
    }
}
