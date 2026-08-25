<?php
namespace App\Domain\Customers;

use InvalidArgumentException;

final readonly class IndonesianMobile
{
    private function __construct(private string $value) {}
    public static function from(string $value): self
    {
        $digits = preg_replace('/\D+/', '', trim($value));
        if (str_starts_with($digits, '62')) $canonical = '+'.$digits;
        elseif (str_starts_with($digits, '0')) $canonical = '+62'.substr($digits, 1);
        else throw new InvalidArgumentException('Nomor ponsel Indonesia tidak valid.');
        if (! preg_match('/^\+628[0-9]{8,11}$/', $canonical)) throw new InvalidArgumentException('Nomor ponsel Indonesia tidak valid.');
        return new self($canonical);
    }
    public function value(): string { return $this->value; }
}
