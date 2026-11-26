<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['name', 'email', 'password', 'role', 'suspended_at'])]
#[Hidden(['password'])]
class AdminUser extends Authenticatable
{
    use HasApiTokens;

    protected $table = 'admins';

    protected function casts(): array
    {
        return ['password' => 'hashed', 'suspended_at' => 'datetime'];
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }
}
