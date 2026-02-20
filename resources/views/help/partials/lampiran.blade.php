<div class="bg-white rounded-lg border border-gray-200 p-4">
    <h3 class="font-semibold text-gray-800 mb-3 flex items-center">
        <i class="fas fa-paperclip text-gray-500 mr-2"></i> Lampiran
    </h3>
    
    <div class="space-y-4">
        @php
            $hasPhotos = $lampiran['all_photos']->count() > 0;
            $hasDocuments = $lampiran['initial']['documents']->count() > 0 || 
                           $lampiran['follow_up']['documents']->count() > 0 || 
                           $lampiran['completion']['documents']->count() > 0;
        @endphp
        
        @if($hasPhotos)
            <!-- Gallery Foto -->
            <div>
                <div class="overflow-x-auto pb-3">
                    <div class="flex space-x-3 min-w-max">
                        @foreach($lampiran['all_photos'] as $photo)
                            @php
                                $badgeText = $photo->badge_text ?? '';
                                $badgeColor = $photo->badge_color ?? 'bg-gray-100 text-gray-800';
                            @endphp
                            <div class="flex-shrink-0 w-36">
                                <div class="aspect-square overflow-hidden rounded-lg border border-gray-300 bg-gray-100 hover:border-blue-400 cursor-pointer relative group transition-all" 
                                     onclick="viewImage('{{ $photo->id }}')">
                                    <img src="{{ $userRole === 'staff' 
                                        ? route('help.proses.lampiran.preview', ['lampiran' => $photo->id]) 
                                        : route('help.tiket.lampiran.preview', ['lampiran' => $photo->id]) }}?thumb=true" 
                                         alt="{{ $photo->nama_file }}"
                                         class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-200">
                                    
                                    @if($badgeText)
                                        <div class="absolute top-1 right-1">
                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-xs font-medium {{ $badgeColor }} border border-white shadow-sm">
                                                {{ $badgeText }}
                                            </span>
                                        </div>
                                    @endif
                                </div>
                                
                                <div class="mt-2 px-1">
                                    <div class="flex items-center justify-between mb-1">
                                        <span class="text-xs text-gray-600 truncate" title="{{ $photo->nama_file }}">
                                            {{ $photo->nama_file }}
                                        </span>
                                        <a href="{{ $userRole === 'staff' 
                                            ? route('help.proses.lampiran.download', ['lampiran' => $photo->id]) 
                                            : route('help.tiket.lampiran.download', ['lampiran' => $photo->id]) }}" 
                                            class="text-xs text-blue-600 hover:text-blue-800 ml-1"
                                            title="Download"
                                            onclick="event.stopPropagation();">
                                            <i class="fas fa-download"></i>
                                        </a>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <span class="text-xs text-gray-500">{{ $photo->created_at->format('H:i') }}</span>
                                        <span class="text-xs text-gray-500">{{ $photo->formatted_size ?? '' }}</span>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif
        
        @if(!$hasPhotos)
            <div class="text-center py-6 border border-dashed border-gray-300 rounded-lg">
                <div class="inline-flex items-center justify-center w-10 h-10 bg-gray-100 rounded-full mb-2">
                    <i class="fas fa-images text-gray-400"></i>
                </div>
                <p class="text-sm text-gray-500 mb-1">Tidak ada foto</p>
                <p class="text-xs text-gray-400">Foto akan ditampilkan di sini</p>
            </div>
        @endif
        
        @if($hasDocuments)
            <!-- Dokumen -->
            <div class="pt-3 border-t border-gray-200">
                <h4 class="text-sm font-medium text-gray-700 mb-2 flex items-center">
                    <i class="fas fa-file-alt mr-1.5 text-gray-500"></i> Dokumen
                    <span class="ml-2 text-xs bg-gray-100 text-gray-800 px-2 py-0.5 rounded-full">
                        {{ $lampiran['initial']['documents']->count() + $lampiran['follow_up']['documents']->count() + $lampiran['completion']['documents']->count() }}
                    </span>
                </h4>
                
                <div class="space-y-2">
                    @foreach($lampiran['initial']['documents'] as $doc)
                        @include('help.partials.document-item', ['doc' => $doc, 'userRole' => $userRole])
                    @endforeach
                    
                    @foreach($lampiran['follow_up']['documents'] as $doc)
                        @include('help.partials.document-item', ['doc' => $doc, 'userRole' => $userRole])
                    @endforeach
                    
                    @foreach($lampiran['completion']['documents'] as $doc)
                        @include('help.partials.document-item', ['doc' => $doc, 'userRole' => $userRole])
                    @endforeach
                </div>
            </div>
        @endif
        
        @if(!$hasPhotos && !$hasDocuments)
            <div class="text-center py-4">
                <div class="inline-flex items-center justify-center w-10 h-10 bg-gray-100 rounded-full mb-2">
                    <i class="fas fa-paperclip text-gray-400"></i>
                </div>
                <p class="text-sm text-gray-500">Tidak ada lampiran</p>
            </div>
        @endif
    </div>
</div>