<?php

namespace App\Console\Commands;

use App\Models\Memos\Memos;
use Illuminate\Console\Command;

class DeleteExpiredDrafts extends Command
{
    protected $signature = 'memo:delete-expired-drafts';
    protected $description = 'Hapus draft memo (beserta lampirannya) yang sudah melewati expires_at';

    public function handle()
    {
        $deleted = 0;

        // chunkById: draft hasil import bisa banyak, jangan load semuanya ke memori sekaligus.
        Memos::with('attachments')
            ->where('status', 'draft')
            ->where('expires_at', '<', now())
            ->chunkById(100, function ($drafts) use (&$deleted) {
                foreach ($drafts as $draft) {
                    foreach ($draft->attachments as $attachment) {
                        $attachment->deleteFile();
                    }
                    $draft->delete();
                    $deleted++;
                }
            });

        $this->info("Deleted {$deleted} expired drafts");

        return self::SUCCESS;
    }
}
