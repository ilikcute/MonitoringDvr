<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class DvrCheck extends Model
{
    use HasFactory;

    protected $fillable = [
        'dvr_id',
        'checked_by_user_id',
        'check_timestamp',
        'is_ping_online',
        'is_time_synced',
        'time_difference_seconds',
        'hdd_status',
        'record_retention_days',
        'camera_working_count',
        'camera_broken_count',
        'network_type',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'check_timestamp' => 'datetime',
            'is_ping_online' => 'boolean',
            'is_time_synced' => 'boolean',
            'time_difference_seconds' => 'integer',
            'record_retention_days' => 'integer',
            'camera_working_count' => 'integer',
            'camera_broken_count' => 'integer',
        ];
    }

    public function dvr(): BelongsTo
    {
        return $this->belongsTo(Dvr::class);
    }

    public function checker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checked_by_user_id');
    }

    protected static function booted(): void
    {
        static::created(function (DvrCheck $check) {
            $dvr = $check->dvr;
            if ($dvr) {
                $dvr->last_check_at = $check->check_timestamp ?? Carbon::now();
                if ($check->is_ping_online) {
                    $dvr->last_seen_at = Carbon::now();
                    $dvr->status = ($check->camera_broken_count > 0 || $check->hdd_status !== 'Normal')
                        ? 'Degraded'
                        : 'Online';
                } else {
                    $dvr->status = 'Offline';
                }
                if ($check->record_retention_days !== null) {
                    $dvr->retention_days = $check->record_retention_days;
                }
                $dvr->saveQuietly();
            }
        });
    }
}
