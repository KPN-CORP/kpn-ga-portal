<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\help_m\HelpLampiran;

class CheckFileAccess
{
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();
        
        if (!$user || !$user->pelanggan) {
            abort(403, 'Authentication required');
        }
        
        // Jika route mengandung lampiran ID, cek authorization
        if ($lampiranId = $request->route('lampiran')) {
            $lampiran = HelpLampiran::findOrFail($lampiranId);
            $tiket = $lampiran->tiket;
            
            $isPelapor = $tiket->pelapor_id === $user->pelanggan->id_pelanggan;
            $isUploader = $lampiran->pengguna_id === $user->pelanggan->id_pelanggan;
            
            if (!$isPelapor && !$isUploader) {
                abort(403, 'Unauthorized access to file');
            }
            
            // Attach lampiran to request untuk digunakan di controller
            $request->attributes->set('verified_lampiran', $lampiran);
        }
        
        return $next($request);
    }
}