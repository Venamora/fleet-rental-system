<?php
namespace App\Domain\Customers;

use InvalidArgumentException;

final readonly class Email
{
    private function __construct(private string $value) {}
    public static function from(string $value): self
    {
        $value = strtolower(trim($value));
        if (! filter_var($value, FILTER_VALIDATE_EMAIL)) throw new InvalidArgumentException('Email tidak valid.');
        return new self($value);
    }
    public function value(): string { return $this->value; }
}
