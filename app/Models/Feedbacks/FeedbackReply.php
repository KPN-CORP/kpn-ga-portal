<?php

namespace App\Models\Feedbacks;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class FeedbackReply extends Model
{
    use HasFactory;

    protected $table = 'feedback_replies';
    protected $fillable = ['feedback_id', 'user_id', 'message'];

    public function feedback()
    {
        return $this->belongsTo(Feedback::class, 'feedback_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}