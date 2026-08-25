<?php

namespace App\Infrastructure\Persistence;

use App\Application\Rentals\RentalReadRepository;
use App\Models\{Customer, Rental, RentalHistoryEvent, Vehicle};

final class EloquentRentalReadRepository implements RentalReadRepository
{
    public function list(array $filters): mixed
    {
        return Rental::with(['vehicle','customer'])->when($filters['status'] ?? null, fn ($q, $v) => $q->where('status', $v))->when($filters['vehicle_id'] ?? null, fn ($q, $v) => $q->where('vehicle_id', $v))->when($filters['customer_id'] ?? null, fn ($q, $v) => $q->where('customer_id', $v))->when($filters['start_date'] ?? null, fn ($q, $v) => $q->whereDate('start_date','>=',$v))->when($filters['end_date'] ?? null, fn ($q, $v) => $q->whereDate('end_date','<=',$v))->latest()->paginate(15)->withQueryString();
    }
    public function find(int $id): mixed { return Rental::with(['vehicle','customer'])->findOrFail($id); }
    public function history(int $id): iterable { return RentalHistoryEvent::where('rental_id', $id)->orderBy('occurred_at')->get(); }
    public function dashboard(string $today): array
    {
        $activeVehicles = Vehicle::whereNull('archived_at')->count();
        $currentlyRented = Rental::whereIn('status', ['active'])->where('start_date', '<=', $today)->where('effective_end_date', '>=', $today)->count();
        return ['total_active_vehicles'=>$activeVehicles,'currently_rented'=>$currentlyRented,'available_today'=>max(0, $activeVehicles - $currentlyRented),'upcoming_bookings'=>Rental::where('status','booked')->where('start_date','>', $today)->count(),'today_rental_total'=>null];
    }
    public function availability(string $start, string $end): iterable
    {
        $vehicles = Vehicle::whereNull('archived_at')->orWhereNotNull('archived_at')->get();
        return $vehicles->map(function (Vehicle $vehicle) use ($start, $end): array {
            $blocked = $vehicle->archived_at || Rental::where('vehicle_id',$vehicle->id)->where(function ($q): void { $q->whereIn('status',['booked','active'])->orWhere(fn ($q) => $q->where('status','cancelled')->whereColumn('effective_end_date','<','end_date')); })->where('start_date','<=',$end)->where('effective_end_date','>=',$start)->exists();
            return ['vehicle'=>$vehicle,'available'=>!$blocked,'reason'=>$blocked ? ($vehicle->archived_at ? 'Kendaraan diarsipkan.' : 'Kendaraan tidak tersedia pada rentang tanggal tersebut.') : null];
        });
    }
    public function vehicles(): iterable { return Vehicle::with(['brand','type'])->whereNull('archived_at')->orderBy('plate')->get(); }
    public function customers(): iterable { return Customer::orderBy('name')->get(); }
    public function vehicleRateCents(int $vehicleId): int { return (int) Vehicle::whereKey($vehicleId)->value('daily_rate_cents'); }
}
