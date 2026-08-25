<?php
namespace App\Models;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;
class User extends Authenticatable
{
    use Notifiable;
    protected $fillable = ['name', 'email', 'password'];
    protected $hidden = ['password', 'remember_token'];
    protected static function booted(): void { static::creating(function (self $user): void { if (! str_starts_with($user->password, '$2y$')) $user->password = Hash::make($user->password); }); }
}
