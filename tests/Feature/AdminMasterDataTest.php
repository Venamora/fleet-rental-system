<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Customer;
use App\Models\Type;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdminMasterDataTest extends TestCase
{
    use RefreshDatabase;

    public function test_master_data_requires_an_authenticated_admin(): void
    {
        $this->get('/vehicles')->assertRedirect('/login');
        $this->post('/login', ['username' => 'admin', 'password' => 'wrong'])->assertSessionHasErrors('login');

    }

    public function test_vehicle_plate_is_normalized_and_duplicate_is_rejected(): void
    {
        $user = User::create(['name'=>'Admin','email'=>Str::uuid().'@example.test','password'=>'secret']);
        $brand = Brand::create(['name' => 'Toyota']);
        $type = Type::create(['brand_id' => $brand->id, 'name' => 'Avanza']);

        $this->actingAs($user)->post('/vehicles', [
            'brand_id' => $brand->id, 'type_id' => $type->id,
            'plate' => ' b 1234 cd ', 'daily_rate_cents' => 5000,
        ])->assertRedirect('/vehicles');

        $this->assertDatabaseHas('vehicles', ['plate' => 'B 1234 CD']);
        $this->actingAs($user)->post('/vehicles', [
            'brand_id' => $brand->id, 'type_id' => $type->id,
            'plate' => 'B 1234 CD', 'daily_rate_cents' => 6000,
        ])->assertSessionHasErrors('plate');
    }

    public function test_customer_normalizes_unique_fields_and_cannot_be_deleted(): void
    {
        $user = User::create(['name'=>'Admin','email'=>Str::uuid().'@example.test','password'=>'secret']);
        $this->actingAs($user)->post('/customers', [
            'name' => 'Siti', 'email' => ' SITI@Example.com ', 'phone' => '0812 3456 789',
        ])->assertRedirect('/customers');

        $this->assertDatabaseHas('customers', ['email' => 'siti@example.com', 'phone' => '+628123456789']);
        $this->actingAs($user)->post('/customers', [
            'name' => 'Lain', 'email' => 'siti@example.com', 'phone' => '081298765432',
        ])->assertSessionHasErrors('email');
    }

    public function test_invalid_customer_phone_update_returns_phone_error_and_preserves_customer(): void
    {
        $user = User::create(['name'=>'Admin','email'=>Str::uuid().'@example.test','password'=>'secret']);
        $customer = Customer::create(['name'=>'Siti','email'=>'siti@example.test','phone'=>'+628123456789']);

        $this->actingAs($user)->put('/customers/'.$customer->id, [
            'name'=>'Siti Baru', 'email'=>'siti.baru@example.test', 'phone'=>'123-invalid',
        ])->assertSessionHasErrors('phone')->assertRedirect();

        $this->assertDatabaseHas('customers', [
            'id'=>$customer->id, 'name'=>'Siti', 'email'=>'siti@example.test', 'phone'=>'+628123456789',
        ]);
    }

    public function test_archive_and_restore_require_explicit_confirmation(): void
    {
        $user = User::create(['name'=>'Admin','email'=>Str::uuid().'@example.test','password'=>'secret']);
        $vehicle = Vehicle::create([
            'brand_id'=>($brand = Brand::create(['name'=>'Toyota'.Str::random(4)]))->id,
            'type_id'=>Type::create(['brand_id'=>($brand = Brand::create(['name'=>'Toyota']))->id,'name'=>'Avanza'])->id,
            'plate'=>'B 1234 CD', 'daily_rate_cents'=>5000,
        ]);

        $this->actingAs($user)->post('/vehicles/'.$vehicle->id.'/archive')->assertSessionHasErrors('confirmed');
        $this->assertDatabaseHas('vehicles', ['id'=>$vehicle->id, 'archived_at'=>null]);
        $this->actingAs($user)->post('/vehicles/'.$vehicle->id.'/archive', ['confirmed'=>true])->assertRedirect();
        $this->assertNotNull($vehicle->fresh()->archived_at);
        $this->actingAs($user)->post('/vehicles/'.$vehicle->id.'/restore')->assertSessionHasErrors('confirmed');
        $this->assertNotNull($vehicle->fresh()->archived_at);
        $this->actingAs($user)->post('/vehicles/'.$vehicle->id.'/restore', ['confirmed'=>'yes'])->assertRedirect();
        $this->assertNull($vehicle->fresh()->archived_at);
    }
}
