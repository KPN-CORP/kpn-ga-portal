<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\TrackRDocument;

class TrackRAccess
{
    public function handle(Request $request, Closure $next)
    {
        // Ambil ID dokumen dari route
        $documentId = $request->route('id') ?? $request->route('documentId');
        
        if ($documentId) {
            $document = TrackRDocument::find($documentId);
            
            if (!$document) {
                abort(404);
            }
            
            // Cek apakah user adalah pengirim atau penerima
            if (auth()->id() !== $document->pengirim_id && 
                auth()->id() !== $document->penerima_id) {
                abort(403, 'Anda tidak memiliki akses ke dokumen ini');
            }
        }
        
        return $next($request);
    }
}