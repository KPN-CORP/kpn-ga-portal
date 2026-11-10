{{-- Rekonsiliasi invoice Grab: angka invoice dibandingkan dengan transaksi BCR yang tersimpan dan fee/PPN yang sudah dibagikan. Hanya Corporate. --}}
@php
  $rp = fn ($n) => 'Rp ' . number_format((float) $n, 0, ',', '.');
  $tgl = fn ($d) => $d ? \Illuminate\Support\Carbon::parse($d)->format('d-m-Y') : '-';
@endphp
<details class="p" open>
  <summary>Rekonsiliasi invoice Grab ({{ $inv->count() }})</summary>
  <p class="mu">Reimbursement invoice harus sama dengan jumlah transaksi BCR, dan fee serta PPN yang dibagikan ke transaksi harus sama dengan angka di invoice. Selain itu, tandanya perlu dicek.</p>
  <div class="w">
    <table>
      <tr>
        <th>Invoice</th><th>Periode</th><th>Jatuh tempo</th>
        <th class="r">Reimbursement invoice</th><th class="r">Transaksi BCR (jumlah)</th><th class="r">Selisih</th>
        <th class="r">Management Fee</th><th class="r">PPN</th><th class="r">Total tagihan</th>
        <th class="r">Fee dan PPN dibagikan</th><th>Status</th>
      </tr>
      @forelse ($inv as $i)
        @php
          $selisih = (int) $i->bcr_total - (int) $i->reimbursement;
          $feeOk = (int) $i->alloc_fee === (int) $i->fee && (int) $i->alloc_vat === (int) $i->vat;
          if ((int) $i->bcr_rows === 0) { $st = 'BCR belum diunggah'; }
          elseif ($selisih !== 0) { $st = 'Total BCR tidak sama dengan invoice'; }
          elseif (!$feeOk) { $st = 'Fee atau PPN belum sama dengan invoice'; }
          else { $st = 'Cocok'; }
        @endphp
        <tr>
          <td>{{ $i->invoice_no }}<br><span class="mu">BCR {{ $i->bcr_no }} · {{ $i->pt_name }}</span></td>
          <td>{{ $tgl($i->period_from) }} s.d. {{ $tgl($i->period_to) }}</td>
          <td>{{ $tgl($i->due_date) }}</td>
          <td class="r">{{ $rp($i->reimbursement) }}</td>
          <td class="r">{{ $rp($i->bcr_total) }}<br><span class="mu">{{ number_format($i->bcr_rows, 0, ',', '.') }} transaksi</span></td>
          <td class="r">{{ $rp($selisih) }}</td>
          <td class="r">{{ $rp($i->fee) }}@if($i->fee_pct)<br><span class="mu">{{ rtrim(rtrim(number_format($i->fee_pct, 2, ',', ''), '0'), ',') }}%</span>@endif</td>
          <td class="r">{{ $rp($i->vat) }}</td>
          <td class="r"><b>{{ $rp($i->total_incl) }}</b></td>
          <td class="r">{{ $rp($i->alloc_fee) }}<br><span class="mu">PPN {{ $rp($i->alloc_vat) }}</span></td>
          <td>{{ $st }}</td>
        </tr>
      @empty
        <tr><td colspan="11" class="mu">Belum ada invoice. Unggah zip dari Grab yang berisi invoice_*.pdf.</td></tr>
      @endforelse
    </table>
  </div>
</details>
