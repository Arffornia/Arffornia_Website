<?php

namespace App\Progression;

final readonly class Finding implements \JsonSerializable
{
    public function __construct(
        public string $severity,
        public string $code,
        public string $subject,
        public string $message,
    ) {
    }

    public function isError(): bool
    {
        return $this->severity === 'error';
    }

    public function jsonSerialize(): array
    {
        return ['severity' => $this->severity, 'code' => $this->code, 'subject' => $this->subject, 'message' => $this->message];
    }
}
