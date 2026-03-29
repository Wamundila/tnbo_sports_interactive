<?php

namespace App\Exceptions;

use Exception;

class ApiException extends Exception
{
    public function __construct(
        protected string $messageText,
        protected string $errorCode,
        protected int $httpStatus,
        protected array $context = [],
    ) {
        parent::__construct($messageText);
    }

    public static function unauthorized(string $message, string $code, array $context = []): self
    {
        return new self($message, $code, 401, $context);
    }

    public static function forbidden(string $message, string $code, array $context = []): self
    {
        return new self($message, $code, 403, $context);
    }

    public static function conflict(string $message, string $code, array $context = []): self
    {
        return new self($message, $code, 409, $context);
    }

    public static function notFound(string $message, string $code, array $context = []): self
    {
        return new self($message, $code, 404, $context);
    }

    public static function unprocessable(string $message, string $code, array $context = []): self
    {
        return new self($message, $code, 422, $context);
    }

    public static function badGateway(string $message, string $code, array $context = []): self
    {
        return new self($message, $code, 502, $context);
    }

    public function status(): int
    {
        return $this->httpStatus;
    }

    public function toArray(): array
    {
        return array_filter([
            'message' => $this->messageText,
            'code' => $this->errorCode,
            'errors' => $this->context ?: null,
        ], static fn (mixed $value): bool => $value !== null);
    }
}
