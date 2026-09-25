<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Store extends Model
{
    use HasFactory;

    protected $fillable = [
        'store_code',
        'store_name',
        'region',
        'address',
        'ip_subnet',
        'contact_person',
        'phone',
        'status',
        'allow_extra_dvr',
    ];

    protected function casts(): array
    {
        return [
            'allow_extra_dvr' => 'boolean',
        ];
    }

    public function dvrs(): HasMany
    {
        return $this->hasMany(Dvr::class)->orderBy('dvr_index');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'Active');
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (blank($term)) {
            return $query;
        }

        return $query->where(function ($q) use ($term) {
            $q->where('store_code', 'like', "%{$term}%")
              ->orWhere('store_name', 'like', "%{$term}%")
              ->orWhere('region', 'like', "%{$term}%");
        });
    }
}
