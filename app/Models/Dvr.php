<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Crypt;

class Dvr extends Model
{
    use HasFactory;

    protected $fillable = [
        'store_id',
        'dvr_index',
        'label',
        'brand',
        'model_series',
        'serial_number',
        'ip_address',
        'http_port',
        'rtsp_port',
        'server_port',
        'total_channels',
        'storage_capacity_tb',
        'retention_days',
        'firmware_version',
        'status',
        'last_seen_at',
        'last_check_at',
    ];

    protected function casts(): array
    {
        return [
            'dvr_index' => 'integer',
            'http_port' => 'integer',
            'rtsp_port' => 'integer',
            'server_port' => 'integer',
            'total_channels' => 'integer',
            'storage_capacity_tb' => 'float',
            'retention_days' => 'integer',
            'last_seen_at' => 'datetime',
            'last_check_at' => 'datetime',
        ];
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function accounts(): HasMany
    {
        return $this->hasMany(DvrAccount::class)->orderBy('account_slot');
    }

    public function checks(): HasMany
    {
        return $this->hasMany(DvrCheck::class)->latest('check_timestamp');
    }

    public function latestCheck(): HasOne
    {
        return $this->hasOne(DvrCheck::class)->latestOfMany('id');
    }

    /**
     * BR-DVR-002: Otomatis buat 5 slot akun departemen saat unit DVR baru dibuat.
     */
    protected static function booted(): void
    {
        static::created(function (Dvr $dvr) {
            $store = $dvr->store;
            $storeCode = $store ? strtolower($store->store_code) : 'tk';
            $index = $dvr->dvr_index;

            $slotConfigs = [
                1 => ['code' => 'IC',  'user_prefix' => 'ic',  'profile' => 'Live View Stockroom & Kasir'],
                2 => ['code' => 'EDP', 'user_prefix' => 'edp', 'profile' => 'Full Admin Access (Config & Firmware)'],
                3 => ['code' => 'SPV', 'user_prefix' => 'spv', 'profile' => 'Live View Area Publik & Entrance'],
                4 => ['code' => 'DEV', 'user_prefix' => 'dev', 'profile' => 'Live View Kasir & Sales Area'],
                5 => ['code' => 'AUD', 'user_prefix' => 'aud', 'profile' => 'Playback & Export Semua Channel'],
            ];

            foreach ($slotConfigs as $slot => $cfg) {
                $department = Department::firstOrCreate(
                    ['code' => $cfg['code']],
                    ['name' => $cfg['code'] . ' Department', 'description' => 'Slot ' . $slot]
                );

                DvrAccount::firstOrCreate(
                    [
                        'dvr_id' => $dvr->id,
                        'account_slot' => $slot,
                    ],
                    [
                        'department_id' => $department->id,
                        'username' => "{$cfg['user_prefix']}_{$storeCode}_dvr{$index}",
                        'encrypted_password' => Crypt::encryptString("Pass#{$cfg['code']}@{$storeCode}"),
                        'permission_profile' => $cfg['profile'],
                        'is_active' => true,
                        'notes' => 'Auto-generated credential slot',
                    ]
                );
            }
        });
    }
}
