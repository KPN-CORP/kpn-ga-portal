<?php

namespace App\Http\Controllers;

use App\Models\TrackRDocument;
use App\Models\TrackRLog;
use App\Models\TrackRFoto;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class TrackRController extends Controller
{
    /* =========================
       INDEX – DOKUMEN YANG BISA DIAKSES
    ========================= */
    public function index(Request $request)
    {
        $user      = auth()->user();
        $dirCounts = TrackRDocument::directionCounts($user);
        $arah      = $this->normalizeArah($request->input('arah'), $user, $dirCounts);
        $tab       = $this->normalizeTab($request->input('tab'), $arah);

        // Hanya pengirim & penerima terakhir (superadmin track: semua)
        // Arah: masuk = dokumen yang saya terima, keluar = dokumen yang saya kirim
        $query = TrackRDocument::with(['pengirim', 'penerima', 'recipients'])
            ->visibleTo($user)
            ->inDirection($arah, $user->id)
            ->inTab($tab, $user->id);

        if ($search = $request->input('search')) {
            $query->where(function($q) use ($search) {
                $q->where('nomor_dokumen', 'like', "%{$search}%")
                  ->orWhere('judul', 'like', "%{$search}%");
            });
        }

        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->from);
        }
        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->to);
        }

        $documents = $query->orderBy('created_at', 'desc')
                         ->paginate(15)
                         ->withQueryString();

        $counts = $dirCounts[$arah];

        return view('track_r.index', compact('documents', 'counts', 'tab', 'arah', 'dirCounts'));
    }

    /* =========================
       CREATE
    ========================= */
    public function create()
    {
        $users = User::where('id', '!=', auth()->id())
                    ->orderBy('name')
                    ->get();
        return view('track_r.create', compact('users'));
    }

    /* =========================
       STORE / KIRIM
    ========================= */
    public function store(Request $request)
    {
        $request->validate([
            'nomor_dokumen' => 'required|unique:track_r_documents',
            'judul' => 'required',
            'penerima_id' => ['required', 'exists:users,id', Rule::notIn([auth()->id()])],
            'keterangan' => 'nullable|string',
            'foto_dokumen.*' => 'nullable|file|max:5120|mimes:jpg,jpeg,png,pdf,doc,docx',
        ]);

        DB::transaction(function () use ($request) {
            $doc = TrackRDocument::create([
                'nomor_dokumen' => $request->nomor_dokumen,
                'judul' => $request->judul,
                'keterangan' => $request->keterangan,
                'pengirim_id' => auth()->id(),
                'penerima_id' => $request->penerima_id,
                'status' => 'dikirim',
            ]);

            $doc->recipients()->attach($request->penerima_id, [
                'received_at' => now(),
                'status'      => 'dikirim',
            ]);

            if ($request->hasFile('foto_dokumen')) {
                $directory = storage_path('app/private/Track/' . $doc->id);
                if (!File::exists($directory)) {
                    File::makeDirectory($directory, 0755, true);
                }

                foreach ($request->file('foto_dokumen') as $file) {
                    $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                    $extension = $file->getClientOriginalExtension();
                    $filename = $originalName . '_' . time() . '_' . uniqid() . '.' . $extension;
                    $filePath = 'Track/' . $doc->id . '/' . $filename;
                    Storage::disk('private')->put($filePath, file_get_contents($file));

                    TrackRFoto::create([
                        'track_r_document_id' => $doc->id,
                        'nama_file' => $filename,
                        'path' => $filePath,
                        'tipe' => $extension,
                        'ukuran' => $file->getSize(),
                    ]);
                }
            }

            TrackRLog::create([
                'track_r_document_id' => $doc->id,
                'aksi' => 'kirim',
                'dari_user_id' => auth()->id(),
                'ke_user_id' => $request->penerima_id,
                'catatan' => 'Dokumen dikirim',
            ]);
        });

        $namaPenerima = User::find($request->penerima_id)->name ?? 'penerima';

        // Arahkan ke tab Menunggu supaya pengirim langsung melihat dokumennya
        return redirect()->route('track-r.index', ['arah' => 'keluar', 'tab' => 'menunggu'])
            ->with('success', 'Dokumen berhasil dikirim ke ' . $namaPenerima . '. Pantau statusnya di Dokumen Keluar > Menunggu.');
    }

    /* =========================
       SHOW – DETAIL
    ========================= */
    public function show($id)
    {
        $document = TrackRDocument::with([
            'logs.dariUser', 'logs.keUser',
            'pengirim', 'penerima', 'fotos', 'recipients'
        ])->findOrFail($id);

        $this->authorizeDocumentAccess($document);

        // Kandidat tujuan teruskan: bukan diri sendiri, bukan pengirim,
        // dan (kecuali kirim ulang setelah ditolak) bukan pemegang saat ini.
        $excluded = [auth()->id(), $document->pengirim_id];
        if ($document->status !== 'ditolak') {
            $excluded[] = $document->penerima_id;
        }

        $users = User::whereNotIn('id', array_unique($excluded))
                    ->orderBy('name')
                    ->get();

        return view('track_r.show', compact('document', 'users'));
    }

    /* =========================
       DOWNLOAD FOTO
    ========================= */
    public function downloadFoto($documentId, $fotoId)
    {
        $document = TrackRDocument::findOrFail($documentId);
        $this->authorizeDocumentAccess($document);

        $foto = TrackRFoto::where('track_r_document_id', $documentId)
                         ->findOrFail($fotoId);

        $path = storage_path('app/private/' . $foto->path);
        if (!file_exists($path)) {
            abort(404);
        }

        return response()->download($path, $foto->nama_file);
    }

    /* =========================
       DELETE FOTO – DISABLED
    ========================= */
    public function deleteFoto($documentId, $fotoId)
    {
        abort(403, 'Fitur hapus foto tidak diizinkan.');
    }

    /* =========================
       TERIMA
       Hanya pemegang saat ini, hanya saat status menunggu (dikirim/diteruskan).
    ========================= */
    public function terima($id)
    {
        DB::transaction(function () use ($id) {
            $doc = TrackRDocument::lockForUpdate()->findOrFail($id);
            $uid = auth()->id();

            if ($uid !== $doc->penerima_id) {
                abort(403, 'Hanya penerima saat ini yang dapat menerima dokumen');
            }
            if (!in_array($doc->status, ['dikirim', 'diteruskan'], true)) {
                abort(422, 'Dokumen ini sudah diproses.');
            }

            $doc->update(['status' => 'diterima']);
            $doc->recipients()->updateExistingPivot($uid, ['status' => 'diterima']);

            TrackRLog::create([
                'track_r_document_id' => $doc->id,
                'aksi' => 'terima',
                'dari_user_id' => $uid,
                'ke_user_id' => $uid,
                'catatan' => 'Dokumen diterima',
            ]);
        });

        return back()->with('success', 'Dokumen diterima. Bisa dilihat lagi di Dokumen Masuk > Diterima.');
    }

    /* =========================
       TOLAK
       Dikembalikan ke pengirim (ke_user_id = pengirim). Pengirim bisa Kirim Ulang.
    ========================= */
    public function tolak(Request $request, $id)
    {
        $request->validate(['catatan' => 'required|string|max:500']);

        DB::transaction(function () use ($request, $id) {
            $doc = TrackRDocument::lockForUpdate()->findOrFail($id);
            $uid = auth()->id();

            if ($uid !== $doc->penerima_id) {
                abort(403, 'Hanya penerima saat ini yang dapat menolak dokumen');
            }
            if (!in_array($doc->status, ['dikirim', 'diteruskan'], true)) {
                abort(422, 'Dokumen ini sudah diproses.');
            }

            $doc->update(['status' => 'ditolak']);
            $doc->recipients()->updateExistingPivot($uid, ['status' => 'ditolak']);

            TrackRLog::create([
                'track_r_document_id' => $doc->id,
                'aksi' => 'tolak',
                'dari_user_id' => $uid,
                'ke_user_id' => $doc->pengirim_id,
                'catatan' => $request->catatan,
            ]);
        });

        return back()->with('success', 'Dokumen ditolak dan dikembalikan ke pengirim');
    }

    /* =========================
       TERUSKAN (tanpa batas) / ALIHKAN / KIRIM ULANG
       - Pemegang saat ini : teruskan saat dikirim/diteruskan/diterima
       - Pengirim          : alihkan selagi menunggu (dikirim/diteruskan),
                             kirim ulang setelah ditolak
       Akses selalu hanya pengirim + penerima terakhir.
    ========================= */
    public function teruskan(Request $request, $id)
    {
        $request->validate([
            'penerima_id' => 'required|exists:users,id',
            'catatan' => 'nullable|string|max:500',
        ]);

        DB::transaction(function () use ($request, $id) {
            $doc   = TrackRDocument::lockForUpdate()->findOrFail($id);
            $uid   = auth()->id();
            $newId = (int) $request->penerima_id;
            $oldId = $doc->penerima_id;

            $isHolder = $uid === $doc->penerima_id;
            $isSender = $uid === $doc->pengirim_id;

            if ($doc->status === 'ditolak') {
                abort_unless($isSender, 403, 'Hanya pengirim yang dapat mengirim ulang dokumen yang ditolak');
                $aksi = 'kirim_ulang';
                $note = 'Dokumen dikirim ulang';
            } elseif ($isHolder && in_array($doc->status, ['dikirim', 'diteruskan', 'diterima'], true)) {
                $aksi = 'teruskan';
                $note = 'Dokumen diteruskan';
            } elseif ($isSender && in_array($doc->status, ['dikirim', 'diteruskan'], true)) {
                $aksi = 'teruskan';
                $note = 'Dokumen dialihkan oleh pengirim';
            } else {
                abort(403, 'Anda tidak diizinkan meneruskan dokumen ini');
            }

            if ($newId === $uid || $newId === $doc->pengirim_id) {
                abort(422, 'Tujuan tidak valid.');
            }
            if ($aksi === 'teruskan' && $newId === $oldId) {
                abort(422, 'Dokumen sudah berada pada penerima tersebut.');
            }

            $doc->fill([
                'status'      => $aksi === 'kirim_ulang' ? 'dikirim' : 'diteruskan',
                'penerima_id' => $newId,
            ]);

            if ($aksi === 'kirim_ulang') {
                // Siklus baru: izinkan notifikasi email & reminder jalan lagi
                // (kolom ini tidak ada di $fillable, jadi pakai forceFill)
                $doc->forceFill([
                    'email_receiver_sent' => 0,
                    'email_accepted_sent' => 0,
                    'email_rejected_sent' => 0,
                    'last_reminder_sent'  => null,
                ]);
            }
            $doc->save();

            if ($aksi === 'teruskan') {
                $doc->recipients()->updateExistingPivot($oldId, ['status' => 'diteruskan']);
            }

            // Penerima baru: pivot dibuat, atau di-reset jika pernah memegang dokumen ini
            $doc->recipients()->syncWithoutDetaching([
                $newId => ['received_at' => now(), 'status' => 'dikirim'],
            ]);

            TrackRLog::create([
                'track_r_document_id' => $doc->id,
                // Kirim ulang dicatat sebagai 'kirim' (enum log tidak diubah); bedanya ada di catatan
                'aksi' => $aksi === 'kirim_ulang' ? 'kirim' : $aksi,
                'dari_user_id' => $uid,
                'ke_user_id' => $newId,
                'catatan' => $request->catatan ?: $note,
            ]);
        });

        return back()->with('success', 'Dokumen berhasil diteruskan');
    }

    /* =========================
       PDF – DIPERBAIKI dengan sanitasi nama file
    ========================= */
    public function pdf($id)
    {
        $document = TrackRDocument::with([
            'logs.dariUser', 'logs.keUser',
            'pengirim', 'penerima', 'fotos', 'recipients'
        ])->findOrFail($id);

        $this->authorizeDocumentAccess($document);

        $pdf = Pdf::loadView('track_r.pdf', compact('document'));

        // Sanitasi nomor dokumen agar tidak mengandung '/' atau '\'
        $safeNomor = str_replace(['/', '\\'], '_', $document->nomor_dokumen);
        // Jika ingin lebih bersih, bisa gunakan Str::slug($document->nomor_dokumen, '_')
        $filename = 'tanda_terima_' . $safeNomor . '.pdf';

        return $pdf->download($filename);
    }

    /* =========================
       EXPORT CSV – SESUAI FILTER & HAK AKSES
    ========================= */
    public function export(Request $request)
    {
        $user = auth()->user();

        $arah = $this->normalizeArah($request->input('arah'), $user);

        $query = TrackRDocument::with(['pengirim', 'penerima', 'recipients'])
            ->visibleTo($user)
            ->inDirection($arah, $user->id)
            ->inTab($this->normalizeTab($request->input('tab'), $arah), $user->id);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nomor_dokumen', 'like', "%{$search}%")
                  ->orWhere('judul', 'like', "%{$search}%");
            });
        }

        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->from);
        }
        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->to);
        }

        $documents = $query->orderBy('created_at', 'desc')->get();

        $filename = 'track_r_documents_' . date('Ymd_His') . '.csv';
        $handle = fopen('php://temp', 'w+');

        // Header CSV
        fputcsv($handle, [
            'Nomor Dokumen',
            'Judul',
            'Pengirim',
            'Penerima Saat Ini',
            'Status Saya',
            'Status Global',
            'Tanggal Kirim',
            'Tanggal Update',
            'Jumlah Penerima Sebelumnya',
            'Daftar Penerima Sebelumnya'
        ]);

        foreach ($documents as $doc) {
            $userStatus = $doc->statusForUser($user);
            $otherRecipients = $doc->recipients->where('id', '!=', $doc->penerima_id);
            $otherNames = $otherRecipients->pluck('name')->implode('; ');

            fputcsv($handle, [
                $doc->nomor_dokumen,
                $doc->judul,
                $doc->pengirim->name ?? '-',
                $doc->penerima->name ?? '-',
                $userStatus['label'],
                $doc->status,
                $doc->created_at->format('d-m-Y H:i'),
                $doc->updated_at->format('d-m-Y H:i'),
                max(0, $otherRecipients->count()),
                $otherNames ?: '-'
            ]);
        }

        rewind($handle);
        $csvContent = stream_get_contents($handle);
        fclose($handle);

        return response($csvContent, 200, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    /* =========================
       PRIVATE – VALIDASI AKSES (dengan superadmin track)
    ========================= */
    // Arah daftar: masuk | keluar | semua (semua hanya superadmin)
    private function normalizeArah(?string $arah, $user, ?array $dirCounts = null): string
    {
        $isSuper = $user->isSuperadminTrack();
        $allowed = $isSuper ? ['masuk', 'keluar', 'semua'] : ['masuk', 'keluar'];

        if (in_array($arah, $allowed, true)) {
            return $arah;
        }
        if ($isSuper) {
            return 'semua';
        }
        // Belum punya dokumen masuk tapi sudah pernah kirim -> buka Dokumen Keluar
        if ($dirCounts && ($dirCounts['masuk']['semua'] ?? 0) === 0 && ($dirCounts['keluar']['semua'] ?? 0) > 0) {
            return 'keluar';
        }
        return 'masuk';
    }

    // Tab yang tersedia bergantung pada arah
    private function normalizeTab(?string $tab, string $arah = 'semua'): string
    {
        $allowed = match ($arah) {
            'masuk'  => ['perlu_tindakan', 'diterima', 'ditolak'],
            'keluar' => ['menunggu', 'diterima', 'ditolak'],
            default  => ['perlu_tindakan', 'menunggu', 'diterima', 'ditolak'],
        };
        return in_array($tab, $allowed, true) ? $tab : 'semua';
    }

    private function authorizeDocumentAccess($document)
    {
        $user = auth()->user();
        if (!$user->isSuperadminTrack() && !$document->hasAccess($user)) {
            abort(403, 'Anda tidak memiliki akses ke dokumen ini');
        }
    }
}