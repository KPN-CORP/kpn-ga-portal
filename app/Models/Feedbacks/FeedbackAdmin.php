<?php

namespace App\Models\Feedbacks;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class FeedbackAdmin extends Model
{
    use HasFactory;

    protected $table = 'feedback_admins';
    protected $fillable = ['user_id'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}