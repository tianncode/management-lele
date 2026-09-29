<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

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

    public function feedings(): HasMany
    {
        return $this->hasMany(Feeding::class, 'created_by');
    }

    public function mortalities(): HasMany
    {
        return $this->hasMany(Mortality::class, 'created_by');
    }

    public function samplings(): HasMany
    {
        return $this->hasMany(Sampling::class, 'created_by');
    }

    public function harvests(): HasMany
    {
        return $this->hasMany(Harvest::class, 'created_by');
    }

    public function purchases(): HasMany
    {
        return $this->hasMany(Purchase::class, 'created_by');
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class, 'created_by');
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class, 'created_by');
    }

    public function otherIncomes(): HasMany
    {
        return $this->hasMany(OtherIncome::class, 'created_by');
    }

    public function capitalTransactions(): HasMany
    {
        return $this->hasMany(CapitalTransaction::class, 'created_by');
    }

    public function cashTransactions(): HasMany
    {
        return $this->hasMany(CashTransaction::class, 'created_by');
    }
}
