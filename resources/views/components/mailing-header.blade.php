<div class="bg-white rounded-xl shadow p-4 flex justify-between items-center">
<div>
<h2 class="text-lg font-semibold">
📦 Mailing Resi: {{ $mailing->mailing_resi }}
</h2>
<p class="text-xs text-gray-500">
{{ $mailing->mailing_pengirim }} → {{ $mailing->mailing_penerima }}
</p>
</div>


<span class="px-3 py-1 text-xs rounded-full
{{ $mailing->mailing_prioritas == 'Segera' ? 'bg-red-100 text-red-700' :
($mailing->mailing_prioritas == 'Penting' ? 'bg-yellow-100 text-yellow-700' : 'bg-gray-100') }}">
{{ $mailing->mailing_prioritas ?? 'Normal' }}
</span>
</div>