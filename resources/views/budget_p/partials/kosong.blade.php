{{-- params: $pesan; opsional: $tautan, $teksTautan --}}
<div class="card" style="text-align:center;color:var(--mu)">
    <p style="margin:6px 0">{{ $pesan }}</p>
    @if(!empty($tautan))<a class="btn" href="{{ $tautan }}">{{ $teksTautan ?? 'Buka' }}</a>@endif
</div>
