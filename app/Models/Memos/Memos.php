<?php

namespace App\Models\Memos;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class Memos extends Model
{
    protected $table = 'memos';

    protected $fillable = [
        'memo_number', 'perihal', 'kepada', 'dari', 'instruksi', 'bank',
        'atas_nama', 'no_rek', 'penandatangan', 'jabatan', 'total_amount',
        'status', 'business_unit', 'team_id', 'admin_id',
        'dynamic_columns_definition', 'created_by', 'expires_at'
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'total_amount' => 'decimal:2',
        'dynamic_columns_definition' => 'array',
    ];

    protected static function booted()
    {
        static::creating(function ($memo) {
            $creator = User::find($memo->created_by);

            // Tentukan tim & admin acuan penomoran berdasarkan penempatan si pembuat di memo_team_members / memo_team_admins.
            $memo->team_id  = $memo->team_id  ?? MemoNumberSetting::resolveTeamId($creator);
            $memo->admin_id = $memo->admin_id ?? MemoNumberSetting::resolveAdminId($creator);

            $memo->memo_number = MemoNumberSetting::generateNumberForAdmin($memo->admin_id);
        });
    }

    // ========== RELATIONSHIPS ==========
    public function items()
    {
        return $this->hasMany(MemosItems::class, 'memo_id');
    }

    public function attachments()
    {
        return $this->hasMany(MemosAttachments::class, 'memo_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function team()
    {
        return $this->belongsTo(MemoTeam::class, 'team_id');
    }

    public function numberingAdmin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    // ========== SCOPE ==========
    public function scopeViewable($query, User $user)
    {
        if ($user->isMemoSuperadmin()) {
            return $query;
        }

        if ($user->isMemoAdmin()) {
            // Admin bisa lihat memo miliknya sendiri + semua memo dari tim yang dia administer
            // (semua admin dalam satu tim melihat data yang sama, sesuai permintaan "admin bisa
            // liat semua tim buat").
            $teamIds = MemoTeamAdmin::where('user_id', $user->id)->pluck('team_id');

            return $query->where(function ($q) use ($user, $teamIds) {
                $q->where('created_by', $user->id)
                  ->orWhereIn('team_id', $teamIds);
            });
        }

        if ($user->isMemoUser()) {
            return $query->where('created_by', $user->id);
        }

        return $query->whereRaw('1 = 0');
    }
}
