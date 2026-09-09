<?php

namespace App\Models\Memos;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class MemoTeamAdmin extends Model
{
    protected $table = 'memo_team_admins';

    protected $fillable = ['team_id', 'user_id', 'jabatan', 'assigned_by'];

    public function team()
    {
        return $this->belongsTo(MemoTeam::class, 'team_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
