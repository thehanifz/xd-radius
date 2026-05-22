<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServiceActionLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'entity_type',
        'entity_id',
        'action',
        'previous_status',
        'new_status',
        'performed_by',
        'performed_at',
        'notes',
    ];

    protected $casts = [
        'performed_at' => 'datetime',
    ];

    /**
     * Helper static untuk mencatat aksi layanan.
     * Semua parameter positional (bukan named) agar kompatibel dengan PHP 8.x.
     *
     * @param string      $entityType  Tipe entitas: 'member' | 'voucher'
     * @param int         $entityId    ID entitas
     * @param string      $action      Aksi: 'isolate' | 'activate' | 'renew' | 'delete'
     * @param string|null $prev        Status sebelumnya
     * @param string      $next        Status sesudahnya
     * @param int|null    $userId      ID user yang melakukan aksi (app_users.id)
     * @param string|null $notes       Catatan opsional
     */
    public static function record(
        string  $entityType,
        int     $entityId,
        string  $action,
        ?string $prev,
        string  $next,
        ?int    $userId = null,
        ?string $notes  = null
    ): self {
        return static::create([
            'entity_type'     => $entityType,
            'entity_id'       => $entityId,
            'action'          => $action,
            'previous_status' => $prev,
            'new_status'      => $next,
            'performed_by'    => $userId,
            'performed_at'    => now(),
            'notes'           => $notes,
        ]);
    }

    // ─── Relationships ────────────────────────────────────────────────────────

    public function performer()
    {
        return $this->belongsTo(AppUser::class, 'performed_by');
    }

    // ─── Scopes ──────────────────────────────────────────────────────────────

    public function scopeForMember($query, int $memberId)
    {
        return $query->where('entity_type', 'member')->where('entity_id', $memberId);
    }

    public function scopeForVoucher($query, int $voucherId)
    {
        return $query->where('entity_type', 'voucher')->where('entity_id', $voucherId);
    }

    // ─── Accessors ────────────────────────────────────────────────────────────

    public function getActionLabelAttribute(): string
    {
        return match ($this->action) {
            'isolate'  => 'Isolir',
            'activate' => 'Aktifkan',
            'renew'    => 'Perpanjang',
            'delete'   => 'Hapus',
            default    => ucfirst($this->action),
        };
    }
}
