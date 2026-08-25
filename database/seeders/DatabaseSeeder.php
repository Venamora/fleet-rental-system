<?php
namespace Database\Seeders;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(BrandTypeSeeder::class);
        $username = env('ADMIN_USERNAME'); $password = env('ADMIN_PASSWORD');
        if (is_string($username) && $username !== '' && is_string($password) && $password !== '') User::updateOrCreate(['email'=>$username], ['name'=>'Admin','password'=>Hash::make($password)]);
    }
}
