<?php

declare(strict_types=1);

namespace Modules\AccessRequest\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AccessRequestMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly string $requesterName,
        public readonly string $companyName,
        public readonly string $subjectLine,
        public readonly string $messageBody,
    ) {}

    public function build(): self
    {
        return $this->subject($this->subjectLine)
            ->markdown('accessrequest::emails.access-request', [
                'message' => $this->messageBody,
            ]);
    }
}
