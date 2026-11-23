<?php

namespace App\Models\Drms;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\BisnisUnit;

class DrmsUserProfile extends Model
{
    protected $table = 'drms_user_profiles';

    protected $fillable = [
        'user_id',
        'business_unit_id',
        'unit',
        'area',
        'approver_user_id',
        'is_approver',
        'is_drms_user',
        'is_drms_admin',
    ];

    protected $casts = [
        'is_approver' => 'boolean',
        'is_drms_user' => 'boolean',
        'is_drms_admin' => 'boolean',
        'is_drms_superadmin' => 'boolean',
    ];

    /** @var array<int,string>|null cache per instance */
    protected ?array $adminAreaCache = null;

    /**
     * Daftar area yang dipegang admin ini (tabel drms_admin_areas).
     * Kosong = lintas semua area di BU-nya (superadmin ditangani terpisah).
     */
    public function adminAreaList(): array
    {
        if ($this->adminAreaCache === null) {
            $this->adminAreaCache = DrmsAdminArea::where('user_id', $this->user_id)
                ->pluck('area')
                ->map(fn ($a) => trim((string) $a))
                ->filter()
                ->unique()
                ->values()
                ->all();
        }
        return $this->adminAreaCache;
    }

    public function holdsArea(?string $area): bool
    {
        $list = $this->adminAreaList();
        if (empty($list)) {
            return true; // lintas area
        }
        $area = strtolower(trim((string) $area));
        foreach ($list as $a) {
            if (strtolower($a) === $area) {
                return true;
            }
        }
        return false;
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function businessUnit()
    {
        return $this->belongsTo(BisnisUnit::class, 'business_unit_id', 'id_bisnis_unit');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approver_user_id');
    }
}