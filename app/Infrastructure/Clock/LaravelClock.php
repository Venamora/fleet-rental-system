<?php

namespace App\Infrastructure\Clock;

use App\Domain\Shared\Contracts\Clock;
use Carbon\CarbonImmutable;
use DateTimeImmutable;

final class LaravelClock implements Clock
{
    public function now(): DateTimeImmutable
    {
        return CarbonImmutable::now('Asia/Jakarta')->toDateTimeImmutable();
    }
}
