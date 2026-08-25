<?php
namespace App\Infrastructure\Persistence;
use App\Application\Shared\Transaction;
use Illuminate\Support\Facades\DB;
final class LaravelTransaction implements Transaction { public function run(\Closure $operation): mixed { return DB::transaction($operation); } }
