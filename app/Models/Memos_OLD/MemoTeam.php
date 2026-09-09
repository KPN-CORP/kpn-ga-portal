<?php

namespace App\Models\Memos;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class MemoTeam extends Model
{
    protected $table = 'memo_teams';

    protected $fillable = ['team_name', 'created_by'];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** Semua admin yang terdaftar di tim ini */
    public function admins()
    {
        return $this->belongsToMany(User::class, 'memo_team_admins', 'team_id', 'user_id')
            ->withPivot('jabatan', 'assigned_by')
            ->withTimestamps();
    }

    /** Semua anggota biasa di tim ini */
    public function members()
    {
        return $this->belongsToMany(User::class, 'memo_team_members', 'team_id', 'user_id')
            ->withPivot('responsible_admin_id', 'assigned_by')
            ->withTimestamps();
    }

    public function memberRows()
    {
        return $this->hasMany(MemoTeamMember::class, 'team_id');
    }

    public function memos()
    {
        return $this->hasMany(Memos::class, 'team_id');
    }
}
