<?php

namespace App\Models\HSRM;

use App\Models\AreaKerja;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class HsrmLog extends Model
{
    protected $table = 'hsrm_logs';
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'action',
        'module',
        'record_id',
        'area_id',
        'item_label',
        'old_data',
        'new_data',
        'created_at',
    ];

    protected $casts = [
        'old_data' => 'array',
        'new_data' => 'array',
        'created_at' => 'datetime',
    ];

    /** action => [judul, grup filter, kelas badge, teks badge] */
    public const EVENTS = [
        'create'             => ['Created', 'created', 'bg-amber-100 text-amber-800', 'Pending'],
        'update'             => ['Update submitted', 'updated', 'bg-amber-100 text-amber-800', 'Pending'],
        'approve'            => ['Approved', 'approved', 'bg-green-100 text-green-800', 'Approved'],
        'revision'           => ['Revision requested', 'revision', 'bg-red-100 text-red-800', 'Revision'],
        'reject_update'      => ['Update rejected', 'revision', 'bg-gray-200 text-gray-800', 'Rejected'],
        'delete'             => ['Deleted', 'updated', 'bg-gray-200 text-gray-800', 'Deleted'],
        'auto_valid'         => ['Recommendation set automatically', 'system', 'bg-indigo-100 text-indigo-800', 'System'],
        'auto_clear_expired' => ['Expired date cleared automatically', 'system', 'bg-indigo-100 text-indigo-800', 'System'],
    ];

    /** Kolom yang tidak ditampilkan di daftar perubahan (noise / teknis). */
    private const IGNORED_FIELDS = [
        'id', 'created_at', 'updated_at', 'created_by', 'updated_by', 'approved_by', 'approved_at',
        'pending_action', 'previous_data', 'old_attachments', 'last_expiry_reminder',
        'last_reminder_milestone', 'revision_notified_at', 'revision_reason', 'business_unit_id',
    ];

    private const FIELD_LABELS = [
        'employee_name' => 'Employee / Company', 'nik' => 'Certificate number',
        'instansi_pengurusan' => 'Issuing authority', 'expired_date' => 'Expired date',
        'status_verif' => 'Verification', 'status_kepemilikan' => 'Ownership',
        'rekomendasi' => 'Recommendation', 'notes' => 'Notes', 'attachment_path' => 'Attachment',
        'photo_path' => 'Photo', 'area_id' => 'Area', 'pic_user_id' => 'PIC',
        'certificate_type_id' => 'Type', 'custom_certificate_type' => 'Custom type',
        'equipment_type_id' => 'Type', 'custom_equipment_type' => 'Custom type',
        'name' => 'Name', 'capacity' => 'Capacity', 'total_items' => 'Total items',
        'location' => 'Location', 'status' => 'Status',
    ];

    /** Snapshot nama (area/tipe/user) agar tidak query berulang. */
    private static array $nameCache = [];

    protected static function booted()
    {
        // Isi otomatis area_id, item_label, created_at supaya semua HsrmLog::create(...) yang
        // sudah ada di controller tidak perlu diubah.
        static::creating(function (HsrmLog $log) {
            $new = is_array($log->new_data) ? $log->new_data : [];
            $old = is_array($log->old_data) ? $log->old_data : [];
            $src = $new ?: $old;

            if ($log->area_id === null) {
                $log->area_id = $src['area_id'] ?? null;
            }
            if ($log->item_label === null) {
                if ($log->module === 'certificate') {
                    $name = $src['employee_name'] ?? null;
                    $nik = $src['nik'] ?? null;
                    $label = trim(implode(' / ', array_filter([$name, $nik])));
                } else {
                    $label = trim((string) ($src['name'] ?? ''));
                }
                $log->item_label = $label !== '' ? $label : null;
            }
            if ($log->created_at === null) {
                // Waktu aplikasi (Asia/Jakarta), bukan default zona waktu server database.
                $log->created_at = now();
            }
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function area()
    {
        return $this->belongsTo(AreaKerja::class, 'area_id', 'id_area_kerja');
    }

    /** [judul, grup, kelas badge, teks badge] untuk tampilan. */
    public function getEventAttribute(): array
    {
        $e = self::EVENTS[$this->action] ?? [ucfirst(str_replace('_', ' ', (string) $this->action)), 'updated', 'bg-gray-200 text-gray-800', ucfirst((string) $this->action)];
        return ['title' => $e[0], 'group' => $e[1], 'badge' => $e[2], 'badge_text' => $e[3]];
    }

    /** Alasan revision (hanya untuk action revision). */
    public function getReasonAttribute(): ?string
    {
        if ($this->action !== 'revision') {
            return null;
        }
        $r = ($this->new_data ?? [])['revision_reason'] ?? null;
        return $r !== null && $r !== '' ? (string) $r : null;
    }

    /** Daftar perubahan [['field','before','after'], ...] antara old_data dan new_data. */
    public function getChangesAttribute(): array
    {
        $old = $this->old_data ?? [];
        $new = $this->new_data ?? [];
        if (!$old || !$new) {
            return [];
        }

        $rows = [];
        foreach ($new as $key => $value) {
            if (in_array($key, self::IGNORED_FIELDS, true)) {
                continue;
            }
            $before = $this->formatValue($key, $old[$key] ?? null);
            $after = $this->formatValue($key, $value);
            if ($before === $after) {
                continue;
            }
            $rows[] = [
                'field' => self::FIELD_LABELS[$key] ?? ucfirst(str_replace('_', ' ', $key)),
                'before' => $before,
                'after' => $after,
            ];
        }
        return $rows;
    }

    private function formatValue(string $key, $value): string
    {
        if ($value === null || $value === '') {
            return '-';
        }
        if (is_array($value)) {
            return json_encode($value, JSON_UNESCAPED_UNICODE);
        }

        switch ($key) {
            case 'status_kepemilikan':
                return $value ? 'Yes' : 'No';
            case 'expired_date':
                try {
                    $c = Carbon::parse($value)->timezone(config('app.timezone'));
                    return $c->format('H:i:s') === '00:00:00' ? $c->format('d M Y') : $c->format('d M Y H:i');
                } catch (\Throwable $e) {
                    return (string) $value;
                }
            case 'rekomendasi':
                return ['recommended' => 'Recommended', 'not_recommended' => 'Not Recommended', 'valid' => 'Valid'][$value] ?? (string) $value;
            case 'status_verif':
            case 'status':
                return ucfirst((string) $value);
            case 'attachment_path':
            case 'photo_path':
                return basename((string) $value);
            case 'area_id':
                return self::lookup('area', 'stock_ctl_area_kerja', 'id_area_kerja', 'nama_area', $value);
            case 'certificate_type_id':
                return self::lookup('cert_type', 'hsrm_certificate_types', 'id', 'name', $value);
            case 'equipment_type_id':
                return self::lookup('eq_type', 'hsrm_equipment_types', 'id', 'name', $value);
            case 'pic_user_id':
                return self::lookup('user', 'users', 'id', 'name', $value);
        }
        return (string) $value;
    }

    private static function lookup(string $bucket, string $table, string $idCol, string $nameCol, $id): string
    {
        $key = $bucket . ':' . $id;
        if (!array_key_exists($key, self::$nameCache)) {
            self::$nameCache[$key] = DB::table($table)->where($idCol, $id)->value($nameCol);
        }
        return self::$nameCache[$key] !== null ? (string) self::$nameCache[$key] : ('#' . $id);
    }
}
