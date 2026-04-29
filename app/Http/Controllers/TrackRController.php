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

class TrackRController extends Controller
{
    /* =========================
       INDEX – DOKUMEN YANG BISA DIAKSES
    ========================= */
    public function index(Request $request)
    {
        $query = TrackRDocument::with(['pengirim', 'penerima', 'recipients'])
            ->where(function($q) {
                $userId = auth()->id();
                $q->where('pengirim_id', $userId)
                  ->orWhereHas('recipients', function($sub) use ($userId) {
                      $sub->where('user_id', $userId);
                  });
            });

        // Pencarian
        if ($search = $request->input('search')) {
            $query->where(function($q) use ($search) {
                $q->where('nomor_dokumen', 'like', "%{$search}%")
                  ->orWhere('judul', 'like', "%{$search}%");
            });
        }

        // Filter status
        if ($status = $request->input('status')) {
            $query->where('status', $status);
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
            // Buat dokumen
            $doc = TrackRDocument::create([
                'nomor_dokumen' => $request->nomor_dokumen,
                'judul' => $request->judul,
                'keterangan' => $request->keterangan,
                'pengirim_id' => auth()->id(),
                'penerima_id' => $request->penerima_id,
                'status' => 'dikirim',
            ]);

            // Tambahkan penerima awal ke tabel recipients
            $doc->recipients()->attach($request->penerima_id, [
                'received_at' => now(),
            ]);

            // Simpan lampiran
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

            // Log pengiriman
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
       SHOW – DETAIL (VALIDASI AKSES)
       TAMBAHAN: passing $users agar form teruskan bisa mencari penerima
    ========================= */
    public function show($id)
    {
        $document = TrackRDocument::with([
            'logs.dariUser', 'logs.keUser',
            'pengirim', 'penerima', 'fotos', 'recipients'
        ])->findOrFail($id);

        $this->authorizeDocumentAccess($document);

        // Data user untuk pencarian penerima di form teruskan (kecuali user sendiri)
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
       TERUSKAN – TAMBAH PENERIMA BARU, YANG LAMA TETAP ADA
    ========================= */
    public function teruskan(Request $request, $id)
    {
        $request->validate([
            'penerima_id' => 'required|exists:users,id',
            'catatan' => 'nullable|string|max:500',
        ]);

        DB::transaction(function () use ($request, $id) {
            $doc = TrackRDocument::findOrFail($id);

            // Izin: hanya penerima saat ini atau pengirim
            if (auth()->id() !== $doc->penerima_id && auth()->id() !== $doc->pengirim_id) {
                abort(403, 'Anda tidak diizinkan meneruskan dokumen ini');
            }

            // Update penerima terbaru & status
            $doc->update([
                'status' => 'diteruskan',
                'penerima_id' => $request->penerima_id,
            ]);

            // Tambahkan penerima baru ke history (tanpa hapus yang lama)
            $doc->recipients()->syncWithoutDetaching([$request->penerima_id => [
                'received_at' => now(),
            ]]);

            // Log
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
       PDF
    ========================= */
    public function pdf($id)
    {
        $document = TrackRDocument::with([
            'logs.dariUser', 'logs.keUser',
            'pengirim', 'penerima', 'fotos', 'recipients'
        ])->findOrFail($id);

        $this->authorizeDocumentAccess($document);

        $pdf = Pdf::loadView('track_r.pdf', compact('document'));
        return $pdf->download('tanda_terima_' . $document->nomor_dokumen . '.pdf');
    }

    /* =========================
       PRIVATE – VALIDASI AKSES (pakai hasAccess)
    ========================= */
    private function authorizeDocumentAccess($document)
    {
        if (!$document->hasAccess(auth()->user())) {
            abort(403, 'Anda tidak memiliki akses ke dokumen ini');
        }
    }
}