<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class JobCreatedMail extends Mailable
{
    use Queueable, SerializesModels;

    private $userName;
    private $addtionalData = [];

    private $subject;
    public function __construct($userName, $subject, $addtionalData)
    {
        $this->userName = $userName;
        $this->subject =$subject;
        $this->addtionalData = $addtionalData;
    }

    public function build()
    {
        return $this->view('emails.job-created')
            ->subject($this->subject);
    }
}