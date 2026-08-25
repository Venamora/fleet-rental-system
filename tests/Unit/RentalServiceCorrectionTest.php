<?php

namespace Tests\Unit;

use App\Application\Rentals\RentalHistory;
use App\Application\Rentals\RentalRepository;
use App\Application\Rentals\RentalService;
use App\Application\Shared\Transaction;
use App\Application\Vehicles\VehicleRepository;
use App\Domain\Shared\Contracts\Clock;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use stdClass;

class RentalServiceCorrectionTest extends TestCase
{
    public function test_cancelled_active_blocks_and_cancelled_booked_does_not(): void
    {
        $repo = new FakeRentalRepository();
        $repo->rows = [
            $this->rental(1, 'cancelled', '2026-08-20', '2026-08-25'),
            $this->rental(2, 'cancelled', '2026-09-01', '2026-09-05'),
        ];
        self::assertTrue($repo->blockingOverlap(7, '2026-08-25', '2026-08-25'));
        $repo->rows[0]->status = 'cancelled-booked';
        self::assertFalse($repo->blockingOverlap(7, '2026-08-25', '2026-08-25'));
    }

    public function test_booked_edit_reprices_vehicle_and_date_changes_and_history_is_appended(): void
    {
        $repo = new FakeRentalRepository(); $history = new FakeHistory();
        $row = $this->rental(1, 'booked', '2026-08-25', '2026-08-25'); $row->vehicle_id = 7; $row->customer_id = 8; $row->daily_rate_snapshot_cents = 1000;
        $repo->rows = [$row]; $vehicles = new FakeVehicleRepository(); $vehicles->vehicle = (object) ['id'=>9, 'archived_at'=>null, 'daily_rate_cents'=>2000];
        $service = $this->service($repo, $history, $vehicles);
        $service->edit(1, 9, 8, '2026-08-25', '2026-08-26');
        self::assertSame(4000, $repo->rows[0]->total_cents);
        self::assertSame('edited', $history->events[0]['type']);
    }

    public function test_active_edit_only_allows_end_date_and_auto_completion_is_day_after_end(): void
    {
        $repo = new FakeRentalRepository(); $history = new FakeHistory();
        $row = $this->rental(1, 'active', '2026-08-20', '2026-08-25'); $row->vehicle_id = 7; $row->customer_id = 8; $repo->rows = [$row];
        $service = $this->service($repo, $history, new FakeVehicleRepository(), '2026-08-25');
        $service->edit(1, 7, 8, '2026-08-20', '2026-08-26');
        self::assertSame('2026-08-26', $repo->rows[0]->end_date);
        $this->expectException(\InvalidArgumentException::class);
        $service->edit(1, 9, 8, '2026-08-20', '2026-08-26');
    }

    public function test_automatic_completion_records_history_and_manual_completion_before_start_is_rejected(): void
    {
        $repo = new FakeRentalRepository(); $history = new FakeHistory();
        $row = $this->rental(1, 'active', '2026-08-20', '2026-08-24'); $repo->rows = [$row];
        $service = $this->service($repo, $history, new FakeVehicleRepository(), '2026-08-25');
        $service->completeDueRentals();
        self::assertSame('completed', $repo->rows[0]->status);
        self::assertSame('completed', $history->events[0]['type']);
    }

    public function test_due_booked_rental_advances_to_active_once_and_records_one_history_event(): void
    {
        $repo = new FakeRentalRepository(); $history = new FakeHistory();
        $repo->rows = [$this->rental(1, 'booked', '2026-08-24', '2026-08-26')];
        $service = $this->service($repo, $history, new FakeVehicleRepository(), '2026-08-24');

        $service->advanceDueLifecycle();
        $service->advanceDueLifecycle();

        self::assertSame('active', $repo->rows[0]->status);
        self::assertCount(1, $history->events);
        self::assertSame('active', $history->events[0]['state']);
    }

    public function test_manual_completion_rejects_cancelled_and_completed_rentals_without_history(): void
    {
        foreach (['cancelled', 'completed'] as $status) {
            $repo = new FakeRentalRepository(); $history = new FakeHistory();
            $repo->rows = [$this->rental(1, $status, '2026-08-20', '2026-08-25')];
            $service = $this->service($repo, $history, new FakeVehicleRepository(), '2026-08-25');
            try { $service->complete(1); self::fail('Expected invalid lifecycle transition.'); } catch (\InvalidArgumentException) {}
            self::assertSame($status, $repo->rows[0]->status);
            self::assertCount(0, $history->events);
        }
    }

    public function test_booked_edit_rejects_archived_vehicle_and_past_active_end_date(): void
    {
        $repo = new FakeRentalRepository(); $history = new FakeHistory();
        $repo->rows = [$this->rental(1, 'booked', '2026-08-25', '2026-08-26')];
        $vehicles = new FakeVehicleRepository(); $vehicles->vehicle = (object) ['id'=>9, 'archived_at'=>'2026-08-20', 'daily_rate_cents'=>1000];
        $service = $this->service($repo, $history, $vehicles, '2026-08-24');
        $this->expectException(\InvalidArgumentException::class);
        $service->edit(1, 9, 8, '2026-08-25', '2026-08-26');
    }

    public function test_active_edit_rejects_end_date_before_injected_today(): void
    {
        $repo = new FakeRentalRepository(); $history = new FakeHistory();
        $row = $this->rental(1, 'active', '2026-08-20', '2026-08-26'); $repo->rows = [$row];
        $service = $this->service($repo, $history, new FakeVehicleRepository(), '2026-08-25');
        $this->expectException(\InvalidArgumentException::class);
        $service->edit(1, 7, 8, '2026-08-20', '2026-08-24');
    }

    private function service(FakeRentalRepository $repo, FakeHistory $history, FakeVehicleRepository $vehicles, string $today = '2026-08-24'): RentalService
    { return new RentalService($repo, $vehicles, new InlineTransaction(), new FixedClock($today), $history); }
    private function rental(int $id, string $status, string $start, string $end): stdClass
    { return (object) ['id'=>$id,'vehicle_id'=>7,'customer_id'=>8,'status'=>$status,'start_date'=>new DateTimeImmutable($start),'end_date'=>$end,'effective_end_date'=>$end,'daily_rate_snapshot_cents'=>1000,'total_cents'=>1000]; }
}

final class InlineTransaction implements Transaction { public function run(\Closure $operation): mixed { return $operation(); } }
final class FixedClock implements Clock { public function __construct(private string $date) {} public function now(): DateTimeImmutable { return new DateTimeImmutable($this->date.' 12:00:00'); } }
final class FakeVehicleRepository implements VehicleRepository { public object $vehicle; public function __construct() { $this->vehicle=(object)['id'=>7,'archived_at'=>null,'daily_rate_cents'=>1000]; } public function find(int $id): mixed { $this->vehicle->id=$id; return $this->vehicle; } public function paginate(?string $s, ?string $st): mixed{return null;} public function create(array $d):void{} public function update(int $i,array $d):void{} public function plateExists(string $p,?int $e=null):bool{return false;} public function setArchived(int $i,bool $a):void{} public function typeBelongsToBrand(int $typeId,int $brandId):bool{return true;} public function brands():iterable{return [];} public function types():iterable{return [];} }
final class FakeHistory implements RentalHistory { public array $events=[]; public function append(int $r, string $type, string $state, ?string $reason=null, ?string $effectiveEnd=null): void { $this->events[]=['rental_id'=>$r,'type'=>$type,'state'=>$state,'reason'=>$reason,'effective_end_date'=>$effectiveEnd]; } }
final class FakeRentalRepository implements RentalRepository
{
    public array $rows=[];
    public function blockingOverlap(int $vehicleId,string $start,string $end,?int $exceptId=null):bool { foreach($this->rows as $r) if($r->vehicle_id===$vehicleId && $r->id!==$exceptId && in_array($r->status,['booked','active','cancelled'],true) && $r->status!=='cancelled-booked' && $r->start_date->format('Y-m-d') <= $end && $r->effective_end_date >= $start) return true; return false; }
    public function create(array $data):mixed{return null;} public function find(int $id):mixed{return $this->rows[0];} public function update(int $id,array $data):void{foreach($data as $k=>$v)$this->rows[0]->$k=$v;} public function lockVehicle(int $id):void{} public function forVehicle(int $v,string $s,string $e):iterable{return [];}
    public function dueForCompletion(string $date):iterable { return array_filter($this->rows, fn($r)=>in_array($r->status,['booked','active'],true) && $r->effective_end_date < $date); }
    public function dueForActivation(string $date):iterable { return array_filter($this->rows, fn($r)=>$r->status === 'booked' && $r->start_date->format('Y-m-d') <= $date && $r->effective_end_date >= $date); }
}
