@php
    $iconClass = '';
    if (str_contains($doc->tipe_file, 'pdf')) {
        $iconClass = 'fas fa-file-pdf text-red-500';
    } elseif (str_contains($doc->tipe_file, 'word')) {
        $iconClass = 'fas fa-file-word text-blue-500';
    } elseif (str_contains($doc->tipe_file, 'excel') || str_contains($doc->tipe_file, 'sheet')) {
        $iconClass = 'fas fa-file-excel text-green-600';
    } else {
        $iconClass = 'fas fa-file text-gray-500';
    }
    
    $uploaderName = $doc->pengguna->user->name ?? $doc->pengguna->nama ?? 'Unknown';
@endphp

<div class="flex items-center justify-between p-2 rounded-lg border border-gray-200 hover:bg-gray-50 transition-colors">
    <div class="flex items-center min-w-0 flex-1">
        <div class="flex-shrink-0 mr-2">
            <div class="w-8 h-8 rounded bg-gray-100 flex items-center justify-center">
                <i class="{{ $iconClass }} text-sm"></i>
            </div>
        </div>
        <div class="min-w-0 flex-1">
            <p class="text-sm font-medium text-gray-900 truncate" title="{{ $doc->nama_file }}">
                {{ $doc->nama_file }}
            </p>
            <div class="flex items-center text-xs text-gray-500">
                <span class="mr-2">{{ $doc->created_at->format('d/m H:i') }}</span>
                <span class="mr-2">{{ $doc->formatted_size ?? '' }}</span>
                <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-xs font-medium {{ $doc->type_badge_color ?? 'bg-gray-100 text-gray-800' }}">
                    {{ $doc->type_label ?? $doc->tipe }}
                </span>
                <span class="ml-2 text-xs text-gray-500 truncate max-w-[100px]">Oleh: {{ $uploaderName }}</span>
            </div>
        </div>
    </div>
    <div class="ml-2">
        <a href="{{ $userRole === 'staff' 
            ? route('help.proses.lampiran.download', ['lampiran' => $doc->id]) 
            : route('help.tiket.lampiran.download', ['lampiran' => $doc->id]) }}" 
            class="text-green-600 hover:text-green-800 p-1.5 rounded-full hover:bg-green-50 transition-colors"
            title="Download">
            <i class="fas fa-download text-sm"></i>
        </a>
    </div>
</div>