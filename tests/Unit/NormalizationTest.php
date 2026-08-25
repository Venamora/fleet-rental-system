<?php

namespace Tests\Unit;

use App\Domain\Customers\Email;
use App\Domain\Customers\IndonesianMobile;
use App\Domain\Vehicles\Plate;
use PHPUnit\Framework\TestCase;

class NormalizationTest extends TestCase
{
    public function test_plate_is_trimmed_and_uppercased(): void
    {
        $this->assertSame('B 1234 CD', Plate::from('  b 1234 cd ')->value());
    }

    public function test_email_is_trimmed_and_lowercased(): void
    {
        $this->assertSame('person@example.com', Email::from(' Person@Example.COM ')->value());
    }

    public function test_indonesian_mobile_is_canonicalized_to_plus_sixty_two(): void
    {
        $this->assertSame('+628123456789', IndonesianMobile::from('0812 3456 789')->value());
    }
}
