<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GaFile;
use App\Models\GaFileAccessRequest;
use App\Services\FileCacheService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * API untuk agent di Local Server KPN. Semua panggilan diinisiasi Local.
 */
class GaAgentController extends Controller
{
    public function heartbeat(Request $request)
    {
        $mode = preg_replace('/[^a-z]/', '', strtolower((string) $request->input('mode', '')));
        $payload = ['at' => now()->toIso8601String(), 'mode' => $mode];

        Cache::put('gafiles.agent.heartbeat', $payload, 3600);
        if ($mode !== '') {
            Cache::put('gafiles.agent.heartbeat.' . $mode, $payload, 86400);
        }

        return response()->json(['ok' => true]);
    }

    // ---- File baru: Cloud -> Local ---------------------------------------------------

    public function pendingSync(Request $request)
    {
        $limit = max(1, min((int) $request->query('limit', 50), 200));

        $rows = GaFile::where('cloud_exists', 1)
            ->where('local_exists', 0)
            ->orderBy('id')
            ->limit($limit)
            ->get(['id', 'disk', 'path', 'size', 'sha256', 'version']);

        return response()->json(['data' => $rows]);
    }

    public function download($id)
    {
        $f = GaFile::findOrFail($id);
        $storage = Storage::disk($f->disk);
        abort_unless($storage->exists($f->path), 404, 'File tidak ada di Cloud');

        return response()->download($storage->path($f->path), basename($f->path), [
            'X-File-Sha256'  => $f->sha256,
            'X-File-Version' => (string) $f->version,
        ]);
    }

    public function synced(Request $request, $id)
    {
        $data = $request->validate([
            'sha256'  => 'required|string|size:64',
            'version' => 'required|integer',
        ]);

        $f = GaFile::findOrFail($id);

        if ((int) $data['version'] !== (int) $f->version || ! hash_equals($f->sha256, strtolower($data['sha256']))) {
            return response()->json(['ok' => false, 'reason' => 'version_changed'], 409);
        }

        $f->update([
            'local_exists'      => true,
            'local_sha256'      => $f->sha256,
            'local_verified_at' => now(),
            'local_rechecked_at' => now(),
            'local_problem'     => null,
            'local_problem_at'  => null,
        ]);

        return response()->json(['ok' => true]);
    }

    // ---- Verifikasi ulang file master di Local ---------------------------------------

    /**
     * Antrean yang harus di-hash ulang agent:
     *  1) file yang pernah ditandai bermasalah (mungkin sudah dipulihkan dari backup),
     *  2) file yang masih ada di Cloud, paling dekat jadwal hapus lebih dulu (wajib segar sebelum dihapus),
     *  3) file yang hanya ada di Local (satu-satunya salinan) - audit bergilir.
     */
    public function verifyQueue(Request $request)
    {
        $limit = max(1, min((int) $request->query('limit', 100), 200));
        $cols = ['id', 'disk', 'path', 'size', 'sha256', 'version'];
        $recheckCloud = now()->subHours((int) config('gafiles.recheck_cloud_hours', 24));
        $auditBefore = now()->subDays((int) config('gafiles.audit_days', 7));

        $rows = GaFile::where('local_exists', 1)->whereNotNull('local_problem_at')
            ->orderBy('local_problem_at')->limit($limit)->get($cols);

        if ($rows->count() < $limit) {
            $more = GaFile::where('local_exists', 1)->whereNull('local_problem_at')->where('cloud_exists', 1)
                ->where(fn ($q) => $q->whereNull('local_rechecked_at')->orWhere('local_rechecked_at', '<', $recheckCloud))
                ->orderByRaw('cloud_delete_after IS NULL')->orderBy('cloud_delete_after')->orderBy('id')
                ->limit($limit - $rows->count())->get($cols);
            $rows = $rows->concat($more);
        }

        if ($rows->count() < $limit) {
            $more = GaFile::where('local_exists', 1)->whereNull('local_problem_at')->where('cloud_exists', 0)
                ->where(fn ($q) => $q->whereNull('local_rechecked_at')->orWhere('local_rechecked_at', '<', $auditBefore))
                ->orderBy('local_rechecked_at')->orderBy('id')
                ->limit($limit - $rows->count())->get($cols);
            $rows = $rows->concat($more);
        }

        return response()->json(['data' => $rows->values()]);
    }

    /**
     * Hasil hash ulang dari agent.
     *  - cocok                         -> tandai segar (local_rechecked_at)
     *  - tidak cocok, Cloud masih ada  -> tandai belum tersalin; agent sync menyalin ulang (self-heal)
     *  - tidak cocok, Cloud sudah tidak ada -> kehilangan data nyata: tandai bermasalah + log kritis
     */
    public function verify(Request $request, $id)
    {
        $data = $request->validate([
            'exists'  => 'required|boolean',
            'sha256'  => 'nullable|string|size:64',
            'version' => 'required|integer',
        ]);

        $f = GaFile::findOrFail($id);

        if ((int) $data['version'] !== (int) $f->version) {
            return response()->json(['ok' => false, 'reason' => 'version_changed'], 409);
        }

        $good = (bool) $data['exists']
            && ! empty($data['sha256'])
            && hash_equals((string) $f->sha256, strtolower($data['sha256']));

        if ($good) {
            $f->update([
                'local_exists'       => true,
                'local_sha256'       => $f->sha256,
                'local_verified_at'  => $f->local_verified_at ?? now(),
                'local_rechecked_at' => now(),
                'local_problem'      => null,
                'local_problem_at'   => null,
            ]);

            return response()->json(['ok' => true, 'status' => 'ok']);
        }

        $reason = ! $data['exists']
            ? 'File master tidak ada di Local'
            : 'Checksum file di Local tidak cocok dengan master';

        if ($f->cloud_exists) {
            $f->update([
                'local_exists'       => false,
                'local_sha256'       => null,
                'local_verified_at'  => null,
                'local_rechecked_at' => null,
                'local_problem'      => null,
                'local_problem_at'   => null,
            ]);
            Log::warning('GaFiles verify: ' . $reason . ' (Cloud masih ada, disalin ulang): ' . $f->path);

            return response()->json(['ok' => true, 'status' => 'resync']);
        }

        $first = $f->local_problem_at === null;
        $f->update([
            'local_problem'      => $reason,
            'local_problem_at'   => $f->local_problem_at ?? now(),
            'local_rechecked_at' => now(),
        ]);
        if ($first) {
            Log::critical('GaFiles KEHILANGAN DATA: ' . $reason . ' dan tidak ada salinan di Cloud: ' . $f->path . ' - pulihkan dari backup VM');
        }

        return response()->json(['ok' => true, 'status' => 'problem']);
    }

    // ---- File lama diminta user: Local -> Cloud --------------------------------------

    public function pendingRequests()
    {
        $rows = GaFileAccessRequest::with('file')
            ->where(function ($q) {
                $q->where('status', 'PENDING')
                  ->orWhere(function ($q2) {
                      $q2->where('status', 'PROCESSING')->where('lease_until', '<', now());
                  });
            })
            ->orderBy('id')
            ->limit(50)
            ->get()
            ->filter(fn ($r) => $r->file)
            ->map(fn ($r) => [
                'id'      => $r->id,
                'file_id' => $r->file_id,
                'disk'    => $r->file->disk,
                'path'    => $r->file->path,
                'size'    => $r->file->size,
                'sha256'  => $r->file->sha256,
                'version' => $r->file->version,
            ])
            ->values();

        return response()->json(['data' => $rows]);
    }

    public function claim($id)
    {
        $lease = (int) config('gafiles.lease_seconds', 120);
        $max = (int) config('gafiles.max_attempts', 5);

        $result = DB::transaction(function () use ($id, $lease, $max) {
            $r = GaFileAccessRequest::whereKey($id)->lockForUpdate()->first();
            if (! $r) {
                return 'missing';
            }

            $claimable = $r->status === 'PENDING'
                || ($r->status === 'PROCESSING' && $r->lease_until && $r->lease_until->isPast());
            if (! $claimable) {
                return 'taken';
            }

            if ($r->attempts >= $max) {
                $r->update(['status' => 'FAILED', 'error' => 'Melebihi batas percobaan', 'completed_at' => now()]);
                return 'exhausted';
            }

            $r->update([
                'status'      => 'PROCESSING',
                'claimed_at'  => now(),
                'lease_until' => now()->addSeconds($lease),
                'attempts'    => $r->attempts + 1,
            ]);

            return 'ok';
        });

        if ($result === 'missing') {
            return response()->json(['ok' => false, 'reason' => 'missing'], 404);
        }
        if ($result !== 'ok') {
            return response()->json(['ok' => false, 'reason' => $result], 409);
        }

        return response()->json(['ok' => true, 'lease_seconds' => $lease]);
    }

    public function upload(Request $request, $id, FileCacheService $svc)
    {
        $request->validate([
            'file'   => 'required|file',
            'sha256' => 'required|string|size:64',
        ]);

        $r = GaFileAccessRequest::with('file')->findOrFail($id);
        if ($r->status !== 'PROCESSING' || ! $r->file) {
            return response()->json(['ok' => false, 'reason' => 'not_processing'], 409);
        }

        [$ok, $msg] = $svc->completeRestore($r->file, $request->file('file'));

        if (! $ok) {
            $r->update(['status' => 'PENDING', 'error' => $msg, 'lease_until' => null]);
            Log::warning('GaFiles restore ditolak: ' . $r->file->path . ' - ' . $msg);

            return response()->json(['ok' => false, 'reason' => $msg], 422);
        }

        $r->update(['status' => 'COMPLETED', 'error' => null, 'lease_until' => null, 'completed_at' => now()]);

        return response()->json(['ok' => true]);
    }

    public function fail(Request $request, $id)
    {
        $data = $request->validate([
            'reason'    => 'required|string|max:500',
            'permanent' => 'sometimes|boolean',
        ]);

        $r = GaFileAccessRequest::with('file')->findOrFail($id);
        $max = (int) config('gafiles.max_attempts', 5);
        $permanent = (bool) ($data['permanent'] ?? false);
        $final = $permanent || $r->attempts >= $max;

        $r->update([
            'status'       => $final ? 'FAILED' : 'PENDING',
            'error'        => $data['reason'],
            'lease_until'  => null,
            'completed_at' => $final ? now() : null,
        ]);

        // Local melaporkan file master hilang/rusak: jangan percaya lagi, cegah penghapusan Cloud
        if ($permanent && $r->file) {
            $r->file->update(['local_exists' => false, 'local_sha256' => null, 'local_verified_at' => null, 'local_rechecked_at' => null]);
            Log::critical('GaFiles: file master di Local bermasalah: ' . $r->file->path . ' - ' . $data['reason']);
        }

        return response()->json(['ok' => true]);
    }
}
