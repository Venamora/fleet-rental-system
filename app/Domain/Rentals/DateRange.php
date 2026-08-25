<?php

namespace App\Domain\Rentals;

use DateTimeImmutable;
use InvalidArgumentException;

final class DateRange
{
    private function __construct(public readonly DateTimeImmutable $start, public readonly DateTimeImmutable $end) {}

    public static function fromStrings(string $start, string $end): self
    {
        $from = DateTimeImmutable::createFromFormat('!Y-m-d', $start);
        $to = DateTimeImmutable::createFromFormat('!Y-m-d', $end);
        if (!$from || !$to || $from->format('Y-m-d') !== $start || $to->format('Y-m-d') !== $end) {
            throw new InvalidArgumentException('Invalid rental date.');
        }
        if ($to < $from) throw new InvalidArgumentException('Rental end date cannot precede start date.');
        return new self($from, $to);
    }

    public function durationDays(): int { return (int) $this->start->diff($this->end)->format('%a') + 1; }
    public function overlaps(self $other): bool { return $this->start <= $other->end && $this->end >= $other->start; }
}
