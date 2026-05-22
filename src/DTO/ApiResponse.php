<?php

namespace App\DTO;

class ApiResponse
{
    public function __construct(
        public bool $success,
        public string $message,
        public mixed $data = null,
        public array $errors = [],
        public array $metadata = []
    ) {}

    public static function success(string $message, mixed $data = null, array $metadata = []): self
    {
        return new self(true, $message, $data, [], $metadata);
    }

    public static function error(string $message, array $errors = [], mixed $data = null): self
    {
        return new self(false, $message, $data, $errors);
    }

    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'message' => $this->message,
            'data' => $this->data,
            'errors' => $this->errors,
            'metadata' => $this->metadata,
            'timestamp' => (new \DateTime())->format('Y-m-d H:i:s')
        ];
    }
}
