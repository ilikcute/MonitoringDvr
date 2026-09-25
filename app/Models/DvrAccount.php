<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;

class DvrAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'dvr_id',
        'department_id',
        'account_slot',
        'username',
        'encrypted_password',
        'permission_profile',
        'is_active',
        'notes',
    ];

    protected $hidden = [
        'encrypted_password',
    ];

    protected function casts(): array
    {
        return [
            'account_slot' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function dvr(): BelongsTo
    {
        return $this->belongsTo(Dvr::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Mendekripsi password yang tersimpan aman dengan AES-256.
     */
    public function getDecryptedPassword(): string
    {
        try {
            return Crypt::decryptString($this->encrypted_password);
        } catch (\Throwable $e) {
            return 'ERROR_DECRYPTING';
        }
    }

    /**
     * Mengenkripsi password baru sebelum disimpan.
     */
    public function setPlainPassword(string $plainPassword): self
    {
        $this->encrypted_password = Crypt::encryptString($plainPassword);
        return $this;
    }
}
