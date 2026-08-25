<?php

namespace App\Application\Rentals;

use App\Application\Shared\Transaction;
use App\Application\Vehicles\VehicleRepository;
use App\Domain\Rentals\DateRange;
use App\Domain\Rentals\Pricing;
use App\Domain\Rentals\RentalStatus;
use App\Domain\Shared\Contracts\Clock;
use InvalidArgumentException;

final class RentalService
{
    public function __construct(private RentalRepository $rentals, private VehicleRepository $vehicles, private Transaction $transaction, private Clock $clock, private RentalHistory $history) {}

    public function create(int $vehicleId, int $customerId, string $start, string $end): mixed
    {
        $range = DateRange::fromStrings($start, $end);
        $today = $this->clock->now()->format('Y-m-d');
        if ($start < $today) throw new InvalidArgumentException('Rental start date cannot be in the past.');
        return $this->transaction->run(function () use ($vehicleId, $customerId, $range): mixed {
            $vehicle = $this->vehicles->find($vehicleId);
            if ($vehicle->archived_at) throw new InvalidArgumentException('Archived vehicles cannot be rented.');
            $this->rentals->lockVehicle($vehicleId);
            if ($this->rentals->blockingOverlap($vehicleId, $range->start->format('Y-m-d'), $range->end->format('Y-m-d'))) throw new InvalidArgumentException('Vehicle is unavailable for these dates.');
            $price = Pricing::calculate($range->durationDays(), (int) $vehicle->daily_rate_cents);
            $rental = $this->rentals->create($price + ['vehicle_id'=>$vehicleId,'customer_id'=>$customerId,'start_date'=>$range->start->format('Y-m-d'),'end_date'=>$range->end->format('Y-m-d'),'effective_end_date'=>$range->end->format('Y-m-d'),'status'=>RentalStatus::BOOKED->value,'daily_rate_snapshot_cents'=>$vehicle->daily_rate_cents]);
            $this->history->append($rental->id, 'created', $rental->status, null, $rental->effective_end_date);
            return $rental;
        });
    }

    public function cancel(int $id, string $reason): void
    {
        if (trim($reason) === '') throw new InvalidArgumentException('Cancellation reason is required.');
        $this->transaction->run(function () use ($id, $reason): void {
            $rental = $this->rentals->find($id);
            if (in_array($rental->status, [RentalStatus::COMPLETED->value, RentalStatus::CANCELLED->value], true)) throw new InvalidArgumentException('Rental cannot be cancelled.');
            $today = $this->clock->now()->format('Y-m-d');
            $data = ['status'=>RentalStatus::CANCELLED->value,'cancellation_reason'=>trim($reason),'cancelled_at'=>$this->clock->now()];
            if ($rental->status === RentalStatus::ACTIVE->value) $data['effective_end_date'] = $today;
            $this->rentals->update($id, $data);
            $this->history->append($id, 'cancelled', RentalStatus::CANCELLED->value, trim($reason), $data['effective_end_date'] ?? $rental->effective_end_date);
        });
    }

    public function availability(int $vehicleId, string $start, string $end): iterable
    {
        $range = DateRange::fromStrings($start, $end);
        return $this->rentals->forVehicle($vehicleId, $range->start->format('Y-m-d'), $range->end->format('Y-m-d'));
    }

    public function complete(int $id): void
    {
        $this->transaction->run(function () use ($id): void {
            $rental = $this->rentals->find($id);
            $today = $this->clock->now()->format('Y-m-d');
            if (in_array($rental->status, [RentalStatus::COMPLETED->value, RentalStatus::CANCELLED->value], true)) throw new InvalidArgumentException('Rental cannot be completed.');
            if ($today < $rental->start_date->format('Y-m-d')) throw new InvalidArgumentException('Rental cannot be completed before it starts.');
            $this->rentals->update($id, ['status' => RentalStatus::COMPLETED->value]);
            $this->history->append($id, 'completed', RentalStatus::COMPLETED->value, null, $rental->effective_end_date);
        });
    }

    public function edit(int $id, int $vehicleId, int $customerId, string $start, string $end): void
    {
        $range = DateRange::fromStrings($start, $end); $today = $this->clock->now()->format('Y-m-d');
        $this->transaction->run(function () use ($id, $vehicleId, $customerId, $range, $today): void {
            $rental = $this->rentals->find($id);
            if ($rental->status === RentalStatus::ACTIVE->value) {
                if ($vehicleId !== $rental->vehicle_id || $customerId !== $rental->customer_id || $range->start->format('Y-m-d') !== $this->dateString($rental->start_date) || $today > $this->dateString($rental->end_date)) throw new InvalidArgumentException('Only an active rental end date may be edited.');
                if ($range->end->format('Y-m-d') < $today) throw new InvalidArgumentException('Rental end date cannot be in the past.');
            } elseif ($rental->status !== RentalStatus::BOOKED->value) throw new InvalidArgumentException('Rental cannot be edited.');
            elseif ($range->start->format('Y-m-d') < $today) throw new InvalidArgumentException('Rental start date cannot be in the past.');
            $vehicle = $this->vehicles->find($vehicleId); if ($rental->status === RentalStatus::BOOKED->value && $vehicle->archived_at) throw new InvalidArgumentException('Archived vehicles cannot be rented.'); $this->rentals->lockVehicle($vehicleId);
            if ($this->rentals->blockingOverlap($vehicleId, $range->start->format('Y-m-d'), $range->end->format('Y-m-d'), $id)) throw new InvalidArgumentException('Vehicle is unavailable for these dates.');
            $data = ['vehicle_id'=>$vehicleId,'customer_id'=>$customerId,'start_date'=>$range->start->format('Y-m-d'),'end_date'=>$range->end->format('Y-m-d'),'effective_end_date'=>$range->end->format('Y-m-d')];
            if ($rental->status === RentalStatus::BOOKED->value && ($vehicleId !== $rental->vehicle_id || $data['start_date'] !== $this->dateString($rental->start_date) || $data['end_date'] !== $this->dateString($rental->end_date))) $data += Pricing::calculate($range->durationDays(), (int) $vehicle->daily_rate_cents) + ['daily_rate_snapshot_cents'=>$vehicle->daily_rate_cents];
            $this->rentals->update($id, $data); $this->history->append($id, 'edited', $rental->status, null, $data['effective_end_date']);
        });
    }

    private function dateString(mixed $date): string { return is_object($date) ? $date->format('Y-m-d') : (string) $date; }

    public function completeDueRentals(): void
    {
        foreach ($this->rentals->dueForCompletion($this->clock->now()->format('Y-m-d')) as $rental) $this->complete($rental->id);
    }

    public function advanceDueLifecycle(): void
    {
        $today = $this->clock->now()->format('Y-m-d');
        $this->transaction->run(function () use ($today): void {
            foreach ($this->rentals->dueForActivation($today) as $rental) {
                if ($rental->status !== RentalStatus::BOOKED->value) continue;
                $this->rentals->lockVehicle($rental->vehicle_id);
                $current = $this->rentals->find($rental->id);
                if ($current->status !== RentalStatus::BOOKED->value) continue;
                $this->rentals->update($rental->id, ['status'=>RentalStatus::ACTIVE->value]);
                $this->history->append($rental->id, 'activated', RentalStatus::ACTIVE->value, null, $rental->effective_end_date);
            }
        });
        $this->completeDueRentals();
    }
}
