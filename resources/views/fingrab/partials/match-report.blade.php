@php
  $rp = fn ($n) => 'Rp ' . number_format((float) $n, 0, ',', '.');
  $tipe = ['id' => 'ID', 'email' => 'Email', 'name' => 'Nama'];
@endphp
<div class="mx" style="display:grid;gap:14px">
  <details class="p">
    <summary>Cocok via email ({{ $M['email']->count() }}{{ $M['email_cut'] ? '+' : '' }} karyawan)</summary>
    <p class="mu">ID di file Grab tidak cocok atau kosong, lalu dicocokkan lewat email ke karyawan HCIS berikut. Periksa apakah pasangannya benar.</p>
    <div class="w">
      <table>
        <tr><th>Nama di Grab</th><th>Email Grab</th><th>ID HCIS</th><th>Nama di HCIS</th><th class="r">Transaksi</th><th class="r">Biaya</th></tr>
        @forelse ($M['email'] as $r)
          <tr>
            <td>{{ $r->nama_grab }}</td><td>{{ $r->email }}</td><td>{{ $r->id_hcis }}</td><td>{{ $r->nama_hcis }}</td>
            <td class="r">{{ number_format($r->transaksi, 0, ',', '.') }}</td><td class="r">{{ $rp($r->total) }}</td>
          </tr>
        @empty
          <tr><td colspan="6" class="mu">Tidak ada.</td></tr>
        @endforelse
      </table>
    </div>
    @if ($M['email_cut'])<p class="mu">Menampilkan 500 teratas.</p>@endif
  </details>

  <details class="p">
    <summary>Cocok via ID tetapi nama berbeda ({{ $M['idname']->count() }}{{ $M['idname_cut'] ? '+' : '' }})</summary>
    <p class="mu">ID cocok dengan HCIS, tetapi nama di Grab tidak sama dengan nama di HCIS. Perbedaan kecil seperti singkatan atau gelar wajar; yang namanya jauh berbeda patut dicek, karena ID bisa saja salah orang.</p>
    <div class="w">
      <table>
        <tr><th>ID</th><th>Nama di Grab</th><th>Nama di HCIS</th><th class="r">Transaksi</th><th class="r">Biaya</th></tr>
        @forelse ($M['idname'] as $r)
          <tr>
            <td>{{ $r->id_hcis }}</td><td>{{ $r->nama_grab }}</td><td>{{ $r->nama_hcis }}</td>
            <td class="r">{{ number_format($r->transaksi, 0, ',', '.') }}</td><td class="r">{{ $rp($r->total) }}</td>
          </tr>
        @empty
          <tr><td colspan="5" class="mu">Tidak ada.</td></tr>
        @endforelse
      </table>
    </div>
    @if ($M['idname_cut'])<p class="mu">Menampilkan 300 teratas.</p>@endif
  </details>

  @if ($M['manual'] !== null)
    <details class="p">
      <summary>Koreksi manual tersimpan ({{ $M['manual']->count() }})</summary>
      <p class="mu">Dipakai otomatis setiap upload BCR berikutnya.</p>
      <div class="w">
        <table>
          <tr><th>Dikenali lewat</th><th>Nilai di file Grab</th><th>ID HCIS</th><th>Nama di HCIS</th><th class="r">Transaksi</th><th>Oleh</th><th>Diubah</th></tr>
          @forelse ($M['manual'] as $r)
            <tr>
              <td>{{ $tipe[$r->match_type] ?? $r->match_type }}</td><td>{{ $r->match_value }}</td><td>{{ $r->employee_id }}</td>
              <td>{{ $r->nama_hcis ?? 'Tidak ada di HCIS' }}</td><td class="r">{{ number_format($r->transaksi, 0, ',', '.') }}</td>
              <td>{{ $r->oleh ?? '-' }}</td><td>{{ $r->updated_at }}</td>
            </tr>
          @empty
            <tr><td colspan="7" class="mu">Belum ada koreksi.</td></tr>
          @endforelse
        </table>
      </div>
    </details>
  @endif
</div>