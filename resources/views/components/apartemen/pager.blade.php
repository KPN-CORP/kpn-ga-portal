@props(['paginator'])
{{-- Pagination seragam. Selalu membawa filter aktif karena paginator dibuat dengan ->withQueryString() di controller. --}}
@if($paginator->total() > 0)
<div class="ap-pager">
    <div class="ap-pager-info">Menampilkan <b>{{ $paginator->firstItem() }}</b>–<b>{{ $paginator->lastItem() }}</b> dari <b>{{ $paginator->total() }}</b></div>
    @if($paginator->hasPages())
    <div class="ap-pager-nav">
        @if($paginator->onFirstPage())
            <span class="ap-btn ap-btn-off"><i class="fas fa-chevron-left text-[10px]"></i>Sebelumnya</span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" class="ap-btn ap-btn-ghost"><i class="fas fa-chevron-left text-[10px]"></i>Sebelumnya</a>
        @endif
        <span class="ap-pager-page">{{ $paginator->currentPage() }} / {{ $paginator->lastPage() }}</span>
        @if($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" class="ap-btn ap-btn-ghost">Berikutnya<i class="fas fa-chevron-right text-[10px]"></i></a>
        @else
            <span class="ap-btn ap-btn-off">Berikutnya<i class="fas fa-chevron-right text-[10px]"></i></span>
        @endif
    </div>
    @endif
</div>
@endif