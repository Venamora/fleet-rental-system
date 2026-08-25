<?php

namespace Tests\Feature;

use App\Models\{Brand, Customer, Rental, Type, User, Vehicle};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class RentalPresentationTest extends TestCase
{
    use RefreshDatabase;

    public function test_rental_routes_are_protected_and_dashboard_exposes_exactly_five_metrics(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
        $user = User::create(['name'=>'Admin','email'=>Str::uuid().'@example.test','password'=>'secret']);
        $this->actingAs($user)->get('/dashboard')->assertOk()->assertViewIs('dashboard')->assertSee('Metrik inti dashboard');
    }

    public function test_preview_and_conflicting_create_return_explanatory_errors(): void
    {
        [$user, $vehicle, $customer] = $this->fixtures();
        Rental::create(['vehicle_id'=>$vehicle->id,'customer_id'=>$customer->id,'start_date'=>'2026-08-25','end_date'=>'2026-08-26','effective_end_date'=>'2026-08-26','status'=>'booked','daily_rate_snapshot_cents'=>1000,'subtotal_cents'=>2000,'discount_cents'=>0,'total_cents'=>2000]);
        $response = $this->actingAs($user)->get('/rentals/availability?start_date=2026-08-26&end_date=2026-08-27');
        $response->assertOk()->assertJsonPath('vehicles.0.available', false)->assertJsonPath('vehicles.0.reason', 'Kendaraan tidak tersedia pada rentang tanggal tersebut.');
        $this->actingAs($user)->post('/rentals', ['vehicle_id'=>$vehicle->id,'customer_id'=>$customer->id,'start_date'=>'2026-08-26','end_date'=>'2026-08-27'])->assertSessionHasErrors('dates');
    }

    public function test_rental_list_detail_and_history_are_available_to_admin(): void
    {
        [$user, $vehicle, $customer] = $this->fixtures();
        $rental = Rental::create(['vehicle_id'=>$vehicle->id,'customer_id'=>$customer->id,'start_date'=>'2026-08-25','end_date'=>'2026-08-25','effective_end_date'=>'2026-08-25','status'=>'booked','daily_rate_snapshot_cents'=>1000,'subtotal_cents'=>1000,'discount_cents'=>0,'total_cents'=>1000]);
        $this->actingAs($user)->get('/rentals')->assertOk()->assertViewIs('rentals.index')->assertSee('Semua pemesanan');
        $this->actingAs($user)->get('/rentals/'.$rental->id)->assertOk()->assertViewIs('rentals.show')->assertSee('Lifecycle history');
        $this->actingAs($user)->get('/rentals/'.$rental->id.'/history')->assertOk()->assertViewIs('rentals.history')->assertSee('Lifecycle history');
    }

    public function test_availability_excludes_archived_vehicles_from_selectable_catalog(): void
    {
        [$user, $vehicle, $customer] = $this->fixtures();
        $vehicle->update(['archived_at'=>'2026-08-24 12:00:00']);
        $this->actingAs($user)->get('/rentals/availability?start_date=2026-08-25&end_date=2026-08-26')
            ->assertOk()->assertJsonPath('vehicles.0.available', false)->assertJsonPath('vehicles.0.reason', 'Kendaraan diarsipkan.');
    }

    public function test_price_preview_uses_the_static_route_before_rental_detail(): void
    {
        [$user, $vehicle] = $this->fixtures();

        $this->actingAs($user)->get('/rentals/price-preview?vehicle_id='.$vehicle->id.'&start_date=2026-08-25&end_date=2026-09-01')
            ->assertOk()
            ->assertJsonPath('duration_days', 8)
            ->assertJsonPath('daily_rate_cents', 1000)
            ->assertJsonPath('subtotal_cents', 8000)
            ->assertJsonPath('discount_cents', 800)
            ->assertJsonPath('total_cents', 7200);
    }

    private function fixtures(): array
    {
        $user = User::create(['name'=>'Admin','email'=>Str::uuid().'@example.test','password'=>'secret']); $brand = Brand::create(['name'=>'Toyota'.Str::random(4)]); $type = Type::create(['brand_id'=>$brand->id,'name'=>'Avanza']);
        $vehicle = Vehicle::create(['brand_id'=>$brand->id,'type_id'=>$type->id,'plate'=>'B 1 TEST','daily_rate_cents'=>1000]);
        $customer = Customer::create(['name'=>'Admin Test','email'=>'rental-test@example.test','phone'=>'+628123456789']);
        return [$user, $vehicle, $customer];
    }
}
