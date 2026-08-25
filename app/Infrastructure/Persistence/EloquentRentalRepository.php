<?php

namespace App\Infrastructure\Persistence;

use App\Application\Rentals\RentalRepository;
use App\Models\Rental;
use App\Models\Vehicle;

final class EloquentRentalRepository implements RentalRepository
{
    public function blockingOverlap(int $vehicleId, string $start, string $end, ?int $exceptId = null): bool
    {
        return Rental::where('vehicle_id', $vehicleId)->where(function ($query): void { $query->whereIn('status', ['booked','active'])->orWhere(function ($query): void { $query->where('status', 'cancelled')->whereColumn('effective_end_date', '<', 'end_date'); }); })
            ->when($exceptId, fn ($query) => $query->where('id', '!=', $exceptId))
            ->where('start_date', '<=', $end)->where('effective_end_date', '>=', $start)->exists();
    }
    public function create(array $data): mixed { return Rental::create($data); }
    public function find(int $id): mixed { return Rental::findOrFail($id); }
    public function update(int $id, array $data): void { $this->find($id)->update($data); }
    public function lockVehicle(int $vehicleId): void { Vehicle::whereKey($vehicleId)->lockForUpdate()->firstOrFail(); }
    public function forVehicle(int $vehicleId, string $start, string $end): iterable
    { return Rental::where('vehicle_id', $vehicleId)->where(function ($query): void { $query->whereIn('status', ['booked','active'])->orWhere(function ($query): void { $query->where('status', 'cancelled')->whereNotNull('cancelled_at')->whereColumn('effective_end_date', '<', 'end_date'); }); })->where('start_date', '<=', $end)->where('effective_end_date', '>=', $start)->get(); }
    public function dueForCompletion(string $date): iterable { return Rental::whereIn('status', ['booked','active'])->where('effective_end_date', '<', $date)->get(); }
    public function dueForActivation(string $date): iterable { return Rental::where('status','booked')->where('start_date','<=',$date)->where('effective_end_date','>=',$date)->get(); }
}
