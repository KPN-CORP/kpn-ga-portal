<?php

namespace App\Console\Commands;

use App\Models\Memos\Memos;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class DeleteExpiredDrafts extends Command
{
    protected $signature = 'memo:delete-expired-drafts';
    protected $description = 'Hapus draft yang sudah lebih dari 24 jam';

    public function handle()
    {
        $drafts = Memos::where('status', 'draft')->where('expires_at', '<', now())->get();
        foreach ($drafts as $draft) {
            foreach ($draft->attachments as $att) Storage::disk('public')->delete($att->file_path);
            $draft->delete();
        }
        $this->info("Deleted {$drafts->count()} expired drafts");
    }
}