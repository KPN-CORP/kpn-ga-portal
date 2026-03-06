<?php
// app/Http/Controllers/Apartemen/QRCodeAdminController.php

namespace App\Http\Controllers\Apartemen;

use App\Http\Controllers\Controller;
use App\Models\Apartemen\ApartemenAccessCode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class QRCodeAdminController extends Controller
{
    public function index(Request $request)
    {
        $query = ApartemenAccessCode::orderBy('created_at', 'desc');
        
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('kode_akses', 'like', "%{$search}%")
                  ->orWhere('nama_akses', 'like', "%{$search}%");
            });
        }
        
        $accessCodes = $query->paginate(10);
        
        return view('apartemen.admin.access-codes', compact('accessCodes'));
    }
    
    public function generate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nama_akses' => 'nullable|string|max:100',
            'tipe' => 'required|in:CHECKIN,CHECKOUT,BOTH',
            'max_uses' => 'nullable|integer|min:1',
            'expired_at' => 'nullable|date|after:now'
        ]);
        
        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }
        
        try {
            // Generate kode akses 6 karakter
            $kodeAkses = ApartemenAccessCode::generateKodeAkses(6);
            
            $accessCode = ApartemenAccessCode::create([
                'kode_akses' => $kodeAkses,
                'nama_akses' => $request->nama_akses,
                'tipe' => $request->tipe,
                'is_active' => true,
                'max_uses' => $request->max_uses,
                'expired_at' => $request->expired_at,
                'used_count' => 0
                // HANYA kolom yang ADA di tabel
            ]);
            
            // Generate QR Code
            $qrCode = QrCode::size(300)->generate(route('apartemen.public.index'));
            
            Log::info('QR Code generated', [
                'user_id' => auth()->id(),
                'kode_akses' => $kodeAkses
            ]);
            
            return view('apartemen.admin.qrcode-result', compact('accessCode', 'qrCode', 'kodeAkses'));
            
        } catch (\Exception $e) {
            Log::error('Error generating QR code: ' . $e->getMessage());
            return back()->with('error', 'Gagal generate QR Code: ' . $e->getMessage());
        }
    }
    
    public function deactivate($id)
    {
        try {
            $accessCode = ApartemenAccessCode::findOrFail($id);
            $accessCode->update(['is_active' => false]);
            return back()->with('success', 'Kode akses berhasil dinonaktifkan');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal menonaktifkan kode akses');
        }
    }
    
    public function activate($id)
    {
        try {
            $accessCode = ApartemenAccessCode::findOrFail($id);
            $accessCode->update(['is_active' => true]);
            return back()->with('success', 'Kode akses berhasil diaktifkan');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal mengaktifkan kode akses');
        }
    }
    
    public function destroy($id)
    {
        try {
            $accessCode = ApartemenAccessCode::findOrFail($id);
            $accessCode->delete();
            return back()->with('success', 'Kode akses berhasil dihapus');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal menghapus kode akses');
        }
    }
}