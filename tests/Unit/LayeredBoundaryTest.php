<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class LayeredBoundaryTest extends TestCase
{
    public function test_master_data_controllers_do_not_depend_on_eloquent_or_database_facades(): void
    {
        foreach (['VehicleController.php', 'CustomerController.php'] as $controller) {
            $source = file_get_contents(__DIR__.'/../../app/Http/Controllers/'.$controller);
            $this->assertStringNotContainsString('App\\Models', $source);
            $this->assertStringNotContainsString('Illuminate\\Support\\Facades\\DB', $source);
        }
    }
}
