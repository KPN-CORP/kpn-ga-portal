<?php

namespace App\Http\Controllers\Feedbacks;

use App\Http\Controllers\Controller;
use App\Models\Feedbacks\Feedback;
use App\Models\Feedbacks\FeedbackReply;
use Illuminate\Http\Request;

class FeedbackController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $feedbacks = Feedback::where('user_id', auth()->id())
                            ->orderBy('created_at', 'desc')
                            ->get();
        return view('feedbacks.index', compact('feedbacks'));
    }

    public function create()
    {
        return view('feedbacks.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'subject' => 'required|string|max:255',
            'message' => 'required|string',
        ]);

        $feedback = Feedback::create([
            'user_id' => auth()->id(),
            'subject' => $request->subject,
            'status' => 'open',
        ]);

        FeedbackReply::create([
            'feedback_id' => $feedback->id,
            'user_id' => auth()->id(),
            'message' => $request->message,
        ]);

        return redirect()->route('feedbacks.show', $feedback->id)
                         ->with('success', 'Feedback berhasil dikirim.');
    }

    public function show($id)
    {
        $feedback = Feedback::where('user_id', auth()->id())
                            ->with('replies.user')
                            ->findOrFail($id);
        return view('feedbacks.show', compact('feedback'));
    }

    public function reply(Request $request, $id)
    {
        $feedback = Feedback::where('user_id', auth()->id())
                            ->where('status', 'open')
                            ->findOrFail($id);

        $request->validate(['message' => 'required|string']);

        FeedbackReply::create([
            'feedback_id' => $feedback->id,
            'user_id' => auth()->id(),
            'message' => $request->message,
        ]);

        return redirect()->route('feedbacks.show', $feedback->id)
                         ->with('success', 'Balasan terkirim.');
    }
}