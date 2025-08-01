<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Address;

class SendOtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public $otp;

    // Constructor to pass OTP to the view
    public function __construct($otp)
    {
        $this->otp = $otp;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(config('mail.from.address'), config('mail.from.name')), // Dynamic from address
            subject: 'Your OTP Code',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'email.otp',
            with: [
                'otp' => $this->otp,  // Pass OTP to the view
            ]
        );
    }
}

