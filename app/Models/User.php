<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'status',
        'branch_id',
        'daily_base_salary',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function branches(): HasMany
    {
        return $this->hasMany(Branch::class, 'owner_id');
    }

    public function ownerDeposit(): HasOne
    {
        return $this->hasOne(OwnerDeposit::class, 'owner_id');
    }

    public function depositHistories(): HasMany
    {
        return $this->hasMany(DepositHistory::class, 'owner_id');
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'customer_id');
    }

    public function galleries(): HasMany
    {
        return $this->hasMany(Gallery::class, 'customer_id');
    }
}
