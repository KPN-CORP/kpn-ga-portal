<?php

namespace App\Models\Memos;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class MemoTeamMember extends Model
{
    protected $table = 'memo_team_members';

    protected $fillable = ['team_id', 'user_id', 'responsible_admin_id', 'assigned_by'];

    public function team()
    {
        return $this->belongsTo(MemoTeam::class, 'team_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function responsibleAdmin()
    {
        return $this->belongsTo(User::class, 'responsible_admin_id');
    }
}
