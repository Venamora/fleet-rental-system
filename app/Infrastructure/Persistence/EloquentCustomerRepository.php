<?php
namespace App\Infrastructure\Persistence;
use App\Application\Customers\CustomerRepository;
use App\Models\Customer;
final class EloquentCustomerRepository implements CustomerRepository
{
    public function paginate(): mixed { return Customer::paginate(15); }
    public function create(array $data): void { Customer::create($data); }
    public function update(int $id, array $data): void { Customer::findOrFail($id)->update($data); }
    public function identityExists(string $email, string $phone, ?int $exceptId = null): bool { return Customer::where('id','!=',$exceptId ?? 0)->where(fn($q)=>$q->where('email',$email)->orWhere('phone',$phone))->exists(); }
}
