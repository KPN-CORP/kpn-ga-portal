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
use Illuminate\Support\Str; // <-- Tambahan untuk sanitasi nama file

class TrackRController extends Controller
{
    /* =========================
       INDEX – DOKUMEN YANG BISA DIAKSES
    ========================= */
    public function index(Request $request)
    {
        $query = TrackRDocument::with(['pengirim', 'penerima', 'recipients']);

        // Jika bukan superadmin track, filter berdasarkan akses biasa
        if (!auth()->user()->isSuperadminTrack()) {
            $query->where(function($q) {
                $userId = auth()->id();
                $q->where('pengirim_id', $userId)
                  ->orWhereHas('recipients', function($sub) use ($userId) {
                      $sub->where('user_id', $userId);
                  });
            });
        }

        if ($search = $request->input('search')) {
            $query->where(function($q) use ($search) {
                $q->where('nomor_dokumen', 'like', "%{$search}%")
                  ->orWhere('judul', 'like', "%{$search}%");
            });
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
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

        return view('track_r.index', compact('documents'));
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
            'penerima_id' => 'required',
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

        return redirect()->route('track-r.index')->with('success', 'Dokumen berhasil dikirim');
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

        $users = User::where('id', '!=', auth()->id())
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
    ========================= */
    public function terima($id)
    {
        DB::transaction(function () use ($id) {
            $doc = TrackRDocument::findOrFail($id);

            if (auth()->id() !== $doc->penerima_id) {
                abort(403, 'Hanya penerima saat ini yang dapat menerima dokumen');
            }

            $doc->update(['status' => 'diterima']);

            TrackRLog::create([
                'track_r_document_id' => $doc->id,
                'aksi' => 'terima',
                'dari_user_id' => auth()->id(),
                'ke_user_id' => auth()->id(),
                'catatan' => 'Dokumen diterima',
            ]);
        });

        return back()->with('success', 'Dokumen diterima');
    }

    /* =========================
       TOLAK
    ========================= */
    public function tolak(Request $request, $id)
    {
        $request->validate(['catatan' => 'required|string|max:500']);

        DB::transaction(function () use ($request, $id) {
            $doc = TrackRDocument::findOrFail($id);

            if (auth()->id() !== $doc->penerima_id) {
                abort(403, 'Hanya penerima saat ini yang dapat menolak dokumen');
            }

            $doc->update(['status' => 'ditolak']);

            TrackRLog::create([
                'track_r_document_id' => $doc->id,
                'aksi' => 'tolak',
                'dari_user_id' => auth()->id(),
                'catatan' => $request->catatan,
            ]);
        });

        return back()->with('success', 'Dokumen ditolak');
    }

    /* =========================
       TERUSKAN
    ========================= */
    public function teruskan(Request $request, $id)
    {
        $request->validate([
            'penerima_id' => 'required|exists:users,id',
            'catatan' => 'nullable|string|max:500',
        ]);

        DB::transaction(function () use ($request, $id) {
            $doc = TrackRDocument::findOrFail($id);

            if (auth()->id() !== $doc->penerima_id && auth()->id() !== $doc->pengirim_id) {
                abort(403, 'Anda tidak diizinkan meneruskan dokumen ini');
            }

            $doc->update([
                'status' => 'diteruskan',
                'penerima_id' => $request->penerima_id,
            ]);

            $doc->recipients()->syncWithoutDetaching([$request->penerima_id => [
                'received_at' => now(),
            ]]);

            TrackRLog::create([
                'track_r_document_id' => $doc->id,
                'aksi' => 'teruskan',
                'dari_user_id' => auth()->id(),
                'ke_user_id' => $request->penerima_id,
                'catatan' => $request->catatan ?? 'Dokumen diteruskan',
            ]);
        });

        return back()->with('success', 'Dokumen diteruskan, penerima sebelumnya tetap dapat mengakses.');
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

        $query = TrackRDocument::with(['pengirim', 'penerima', 'recipients']);

        // Jika bukan superadmin track, filter berdasarkan akses biasa
        if (!$user->isSuperadminTrack()) {
            $query->where(function ($q) use ($user) {
                $q->where('pengirim_id', $user->id)
                  ->orWhereHas('recipients', function ($sub) use ($user) {
                      $sub->where('user_id', $user->id);
                  });
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

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
            'Jumlah Penerima Lain',
            'Daftar Penerima Lain'
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
    private function authorizeDocumentAccess($document)
    {
        $user = auth()->user();
        if (!$user->isSuperadminTrack() && !$document->hasAccess($user)) {
            abort(403, 'Anda tidak memiliki akses ke dokumen ini');
        }
    }
}