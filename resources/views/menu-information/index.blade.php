@extends('layouts.app-sidebar')

@section('content')
<div class="max-w-6xl mx-auto px-3 sm:px-6 lg:px-8 py-6">

    {{-- HEADER --}}
    <div class="mb-6">
        <div class="mb-4">
            <h1 class="text-xl font-semibold text-gray-900">Informasi Menu</h1>
            <p class="text-gray-600 text-xs mt-1">Panduan penggunaan sistem GA Portal</p>
        </div>
        
        {{-- SEARCH --}}
        <div class="relative mb-4">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
            </div>
            <input
                type="text"
                id="searchMenu"
                placeholder="Cari menu..."
                class="pl-10 pr-4 py-2 w-full text-sm border border-gray-300 rounded-lg 
                       focus:ring-1 focus:ring-blue-500 focus:border-blue-500"
            >
        </div>
        
        {{-- STATS --}}
        <div class="flex flex-wrap items-center gap-2 text-xs text-gray-600 border-b pb-3">
            <span class="bg-gray-100 px-2 py-1 rounded">{{ $menus->count() }} Menu</span>
            <span class="bg-amber-50 text-amber-700 px-2 py-1 rounded">{{ $menus->whereNotNull('notes')->count() }} Catatan</span>
            <span class="bg-blue-50 text-blue-700 px-2 py-1 rounded">{{ $menus->where('description', '!=', '')->count() }} Terdokumentasi</span>
        </div>
    </div>

    {{-- MENU LIST --}}
    <div class="space-y-3" id="menuList">
        @foreach($menus as $menu)
        <div class="menu-item bg-white border border-gray-200 rounded-lg hover:border-gray-300 transition-colors cursor-pointer"
             data-search="{{ strtolower($menu->menu_name.' '.$menu->menu_key.' '.$menu->description.' '.$menu->notes) }}"
             onclick="toggleDetails({{ $menu->id }})">

            {{-- CARD CONTENT --}}
            <div class="p-4">
                <div class="flex flex-col gap-3">
                    {{-- TITLE ROW --}}
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <h3 class="menu-title text-base font-medium text-gray-900 line-clamp-1">
                                {{ $menu->menu_name }}
                            </h3>
                        </div>
                        <span class="px-2 py-1 rounded text-xs font-medium ml-2 flex-shrink-0
                              @if($menu->menu_key == 'main') bg-blue-50 text-blue-700 border border-blue-100
                              @elseif($menu->menu_key == 'feature') bg-green-50 text-green-700 border border-green-100
                              @elseif($menu->menu_key == 'admin') bg-purple-50 text-purple-700 border border-purple-100
                              @else bg-gray-50 text-gray-700 border border-gray-100 @endif">
                            {{ strtoupper($menu->menu_key) }}
                        </span>
                    </div>

                    {{-- ICON & METADATA ROW --}}
                    <div class="flex items-center gap-3">
                       
                        <div class="flex-1 flex items-center gap-3 text-xs text-gray-500">
                            @if($menu->notes)
                            <span class="inline-flex items-center gap-1 whitespace-nowrap">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                Catatan
                            </span>
                            @endif
                            
                            @if(strlen($menu->description) > 100)
                            <span class="inline-flex items-center gap-1 whitespace-nowrap">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                                </svg>
                                Detail
                            </span>
                            @endif
                        </div>
                    </div>

                    {{-- DESCRIPTION PREVIEW --}}
                    @if($menu->description)
                    <div id="preview-{{ $menu->id }}">
                        <p class="menu-desc text-gray-600 text-xs leading-relaxed line-clamp-2">
                            {{ $menu->description }}
                        </p>
                    </div>
                    @endif

                    {{-- EXPAND INDICATOR --}}
                    <div class="flex justify-end">
                        <div class="text-blue-600 text-xs font-medium flex items-center gap-1">
                            <span id="indicator-text-{{ $menu->id }}">Lihat detail</span>
                            <svg id="chevron-{{ $menu->id }}" class="w-3 h-3 transform transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </div>
                    </div>
                </div>
            </div>

            {{-- EXPANDABLE DETAILS --}}
            <div id="details-{{ $menu->id }}" class="hidden border-t border-gray-100 bg-gray-50 rounded-b-lg">
                <div class="p-4 space-y-3">
                    {{-- FULL DESCRIPTION --}}
                    @if($menu->description)
                    <div>
                        <div class="text-gray-700 text-sm leading-relaxed whitespace-pre-line">
                            {{ $menu->description }}
                        </div>
                    </div>
                    @endif

                    {{-- NOTES --}}
                    @if($menu->notes)
                    <div class="bg-amber-50 border border-amber-100 rounded p-3">
                        <div class="flex items-start gap-2">
                            <svg class="w-3.5 h-3.5 text-amber-600 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <div class="flex-1">
                                <h4 class="text-amber-800 font-medium text-xs mb-1">Catatan Penting</h4>
                                <div class="text-amber-700 text-xs leading-relaxed whitespace-pre-line">
                                    {{ $menu->notes }}
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
        @endforeach
    </div>

    {{-- EMPTY STATE --}}
    <div id="emptyState" class="hidden text-center py-8">
        <svg class="w-10 h-10 text-gray-300 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <h3 class="text-sm font-medium text-gray-600 mb-1">Tidak ditemukan</h3>
        <p class="text-gray-500 text-xs">Coba gunakan kata kunci lain</p>
    </div>

</div>

<style>
.line-clamp-1 {
    display: -webkit-box;
    -webkit-line-clamp: 1;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.rotate-180 {
    transform: rotate(180deg);
}

/* Mobile optimization */
@media (max-width: 640px) {
    .menu-item {
        padding: 0;
    }
    
    .menu-title {
        font-size: 15px;
    }
    
    .menu-desc {
        font-size: 13px;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Search functionality
    const searchInput = document.getElementById('searchMenu');
    const menuItems = document.querySelectorAll('.menu-item');
    const emptyState = document.getElementById('emptyState');

    function performSearch() {
        const q = this.value.toLowerCase().trim();
        let visibleCount = 0;

        menuItems.forEach(item => {
            const searchData = item.dataset.search;
            const title = item.querySelector('.menu-title').textContent.toLowerCase();
            const desc = item.querySelector('.menu-desc')?.textContent.toLowerCase() || '';
            
            const isMatch = searchData.includes(q) || 
                           title.includes(q) || 
                           desc.includes(q);
            
            if (isMatch || q === '') {
                item.style.display = 'block';
                visibleCount++;
                
                // Highlight matching text
                if (q) {
                    highlightText(item, q);
                } else {
                    removeHighlight(item);
                }
            } else {
                item.style.display = 'none';
                removeHighlight(item);
            }
        });

        // Show/hide empty state
        emptyState.classList.toggle('hidden', visibleCount > 0 || q === '');
    }

    function highlightText(item, query) {
        const title = item.querySelector('.menu-title');
        const desc = item.querySelector('.menu-desc');
        
        if (title) {
            const text = title.textContent;
            const regex = new RegExp(`(${query})`, 'gi');
            title.innerHTML = text.replace(regex, '<mark class="bg-yellow-200 px-0.5 rounded">$1</mark>');
        }
        
        if (desc) {
            const text = desc.textContent;
            const regex = new RegExp(`(${query})`, 'gi');
            desc.innerHTML = text.replace(regex, '<mark class="bg-yellow-200 px-0.5 rounded">$1</mark>');
        }
    }

    function removeHighlight(item) {
        const title = item.querySelector('.menu-title');
        const desc = item.querySelector('.menu-desc');
        
        if (title) {
            title.innerHTML = title.textContent;
        }
        if (desc) {
            desc.innerHTML = desc.textContent;
        }
    }

    // Event listener
    searchInput.addEventListener('input', performSearch);
    
    // Keyboard shortcut
    document.addEventListener('keydown', function(e) {
        if (e.key === '/' && document.activeElement !== searchInput) {
            e.preventDefault();
            searchInput.focus();
        }
        if (e.key === 'Escape' && document.activeElement === searchInput) {
            searchInput.value = '';
            performSearch.call(searchInput);
        }
    });

    // Initialize
    performSearch.call(searchInput);
});

// Toggle details
window.toggleDetails = function(id) {
    const details = document.getElementById('details-' + id);
    const preview = document.getElementById('preview-' + id);
    const chevron = document.getElementById('chevron-' + id);
    const indicatorText = document.getElementById('indicator-text-' + id);
    const isHidden = details.classList.contains('hidden');
    
    // Close all other details
    document.querySelectorAll('[id^="details-"]').forEach(el => {
        if (el.id !== 'details-' + id) {
            el.classList.add('hidden');
            // Show preview for other items
            const otherId = el.id.replace('details-', '');
            const otherPreview = document.getElementById('preview-' + otherId);
            const otherChevron = document.getElementById('chevron-' + otherId);
            const otherIndicator = document.getElementById('indicator-text-' + otherId);
            
            if (otherPreview) {
                otherPreview.classList.remove('hidden');
            }
            if (otherChevron) {
                otherChevron.classList.remove('rotate-180');
            }
            if (otherIndicator) {
                otherIndicator.textContent = 'Lihat detail';
            }
        }
    });
    
    // Toggle current
    if (isHidden) {
        details.classList.remove('hidden');
        // Hide preview
        if (preview) {
            preview.classList.add('hidden');
        }
        if (chevron) {
            chevron.classList.add('rotate-180');
        }
        if (indicatorText) {
            indicatorText.textContent = 'Tutup detail';
        }
        // Scroll to ensure visibility
        setTimeout(() => {
            details.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }, 100);
    } else {
        details.classList.add('hidden');
        // Show preview
        if (preview) {
            preview.classList.remove('hidden');
        }
        if (chevron) {
            chevron.classList.remove('rotate-180');
        }
        if (indicatorText) {
            indicatorText.textContent = 'Lihat detail';
        }
    }
};
</script>
@endsection