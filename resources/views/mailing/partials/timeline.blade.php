<div class="border-l-2 border-gray-200 ml-2 pl-4 space-y-4 text-xs">
    <div class="relative">
        <span class="absolute -left-[11px] top-0 w-3 h-3 bg-blue-500 rounded-full"></span>
        <div class="font-semibold">Diterima</div>
        <div class="text-gray-500">
            @if($m->mailing_tanggal_input)
                {{ $m->mailing_tanggal_input->format('d M Y H:i') }}
            @else
                -
            @endif
        </div>
    </div>

    @if($m->mailing_tanggal_ob47)
    <div class="relative">
        <span class="absolute -left-[11px] top-0 w-3 h-3 bg-indigo-500 rounded-full"></span>
        <div class="font-semibold">Lantai 47</div>
        <div class="text-gray-500">{{ $m->mailing_tanggal_ob47->format('d M Y H:i') }}</div>
    </div>
    @endif

    @if($m->mailing_tanggal_distribusi)
    <div class="relative">
        <span class="absolute -left-[11px] top-0 w-3 h-3 bg-yellow-500 rounded-full"></span>
        <div class="font-semibold">Distribusi</div>
        <div class="text-gray-500">{{ $m->mailing_tanggal_distribusi->format('d M Y H:i') }}</div>
    </div>
    @endif

    @if($m->mailing_tanggal_selesai)
    <div class="relative">
        <span class="absolute -left-[11px] top-0 w-3 h-3 bg-green-500 rounded-full"></span>
        <div class="font-semibold">Selesai</div>
        <div class="text-gray-500">{{ $m->mailing_tanggal_selesai->format('d M Y H:i') }}</div>
    </div>
    @endif
</div>