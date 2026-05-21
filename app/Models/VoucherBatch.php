<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VoucherBatch extends Model
{
    protected $table = 'voucher_batches';

    protected $fillable = [
        'batch_code',
        'prefix',
        'length',
        'charset_mode',
        'quantity',
        'plan_id',
        'generated_by',
        'generated_at',
        'notes',
    ];

    protected $casts = [
        'generated_at' => 'datetime',
        'length'       => 'integer',
        'quantity'     => 'integer',
    ];

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function generatedBy(): BelongsTo
    {
        return $this->belongsTo(AppUser::class, 'generated_by');
    }

    public function vouchers(): HasMany
    {
        return $this->hasMany(Voucher::class, 'batch_id');
    }

    public function getCharsetLabelAttribute(): string
    {
        return match ($this->charset_mode) {
            'numeric'    => 'Angka',
            'alpha_upper'=> 'Huruf Besar',
            'alpha_lower'=> 'Huruf Kecil',
            'alpha'      => 'Huruf Campuran',
            'alphanumeric' => 'Huruf + Angka',
            default      => $this->charset_mode,
        };
    }
}
