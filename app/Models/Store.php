<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Store extends Model
{
    protected $fillable = [
        'name',
        'code',
        'address',
        'city',
        'state',
        'postal_code',
        'phone',
        'email',
        'gst_number',
        'is_active',
        'settings',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'settings' => 'array',
        ];
    }

    public function staff(): HasMany
    {
        return $this->hasMany(Staff::class);
    }

    public function registers(): HasMany
    {
        return $this->hasMany(PosRegister::class);
    }

    public function sales(): HasMany
    {
        return $this->hasMany(PosSale::class);
    }

    public function shifts(): HasMany
    {
        return $this->hasMany(StaffShift::class);
    }
}
