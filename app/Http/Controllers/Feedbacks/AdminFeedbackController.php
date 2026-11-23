<?php
// app/Http/Controllers/Feedbacks/AdminFeedbackController.php

namespace App\Http\Controllers\Feedbacks;

use App\Http\Controllers\Controller;
use App\Models\Feedbacks\Feedback;
use App\Models\Feedbacks\FeedbackReply;
use Illuminate\Http\Request;

class AdminFeedbackController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('feedback.admin');
    }

    public function index()
    {
        $feedbacks = Feedback::with('user', 'replies')
                            ->orderBy('created_at', 'desc')
                            ->get();
        return view('feedbacks.admin.index', compact('feedbacks'));
    }

    public function show($id)
    {
        $feedback = Feedback::with('replies.user', 'user')->findOrFail($id);
        
        // Mark as read for replies from user (not admin)
        FeedbackReply::where('feedback_id', $feedback->id)
            ->where('user_id', '!=', auth()->id())
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);

        return view('feedbacks.admin.show', compact('feedback'));
    }

    public function reply(Request $request, $id)
    {
        $feedback = Feedback::findOrFail($id);
        $request->validate(['message' => 'required|string']);

        FeedbackReply::create([
            'feedback_id' => $feedback->id,
            'user_id' => auth()->id(),
            'message' => $request->message,
        ]);

        if ($feedback->status === 'closed') {
            $feedback->update(['status' => 'open']);
        }

        return redirect()->route('feedbacks.admin.show', $feedback->id)
                         ->with('success', 'Balasan admin terkirir.');
    }

    public function toggleStatus($id)
    {
        $feedback = Feedback::findOrFail($id);
        $feedback->status = $feedback->status === 'open' ? 'closed' : 'open';
        $feedback->save();

        return redirect()->route('feedbacks.admin.show', $feedback->id)
                         ->with('success', 'Status berhasil diubah.');
    }
}