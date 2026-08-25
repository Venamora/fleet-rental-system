<?php
namespace App\Domain\Vehicles;

use InvalidArgumentException;

final readonly class Plate
{
    private function __construct(private string $value) {}
    public static function from(string $value): self
    {
        $value = strtoupper(trim($value));
        if ($value === '') throw new InvalidArgumentException('Plat nomor wajib diisi.');
        return new self($value);
    }
    public function value(): string { return $this->value; }
}
