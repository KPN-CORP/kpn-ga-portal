@extends('layouts.app_memos')
@section('title', isset($memo) ? 'Edit Draft Memo' : 'Buat Memo Baru')
@section('content')
<div x-data="memoCreator()" x-init="init()" class="w-full px-2 md:px-4">
    <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
        <!-- Panel Form -->
        <div class="bg-white rounded-xl shadow-sm p-5">
            <h2 class="text-xl font-bold mb-4">📝 {{ isset($memo) ? 'Edit Draft Memo' : 'Buat E-Memo' }}</h2>

            <!-- Template Tim -->
            <div class="bg-blue-50 border border-blue-100 rounded-lg p-3 mb-4">
                <div class="flex justify-between items-center mb-2">
                    <label class="block text-sm font-medium text-gray-700">📂 Template Tim</label>
                    <span class="text-xs text-gray-500" x-text="templates.length + '/15 template'"></span>
                </div>
                <div class="flex gap-2">
                    <select x-model="selectedTemplateId" class="flex-1 border rounded-lg p-2 text-sm bg-white">
                        <option value="">-- Pilih template, atau langsung bikin memo baru --</option>
                        <template x-for="t in templates" :key="t.id">
                            <option :value="t.id" x-text="t.team ? (t.name + ' — ' + t.team.team_name) : t.name"></option>
                        </template>
                    </select>
                    <button type="button" @click="loadTemplate" :disabled="!selectedTemplateId" class="text-sm bg-blue-600 disabled:bg-gray-300 text-white px-3 py-2 rounded-lg hover:bg-blue-700 transition">Muat</button>
                    <button type="button" @click="deleteTemplate" :disabled="!selectedTemplateId" class="text-sm bg-red-100 disabled:bg-gray-100 text-red-600 disabled:text-gray-400 px-3 py-2 rounded-lg hover:bg-red-200 transition">🗑️</button>
                </div>
                <div class="flex gap-3 mt-2">
                    <button type="button" @click="saveAsTemplate" class="text-xs text-blue-700 hover:underline">💾 Simpan isian saat ini sebagai template baru</button>
                    <button type="button" @click="updateTemplate" x-show="selectedTemplateId" class="text-xs text-amber-700 hover:underline">✏️ Timpa template terpilih dengan isian saat ini</button>
                </div>
                <p class="text-xs text-gray-400 mt-1">Template menyimpan seluruh isian (header, rincian, rekening) dan bisa dipakai &amp; diedit oleh siapa saja di tim Anda.</p>
            </div>

            <!-- Form Fields -->
            <div class="grid grid-cols-2 gap-3 mt-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Kepada</label>
                    <input type="text" x-model="form.kepada" class="w-full border rounded-lg p-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Dari</label>
                    <input type="text" x-model="form.dari" class="w-full border rounded-lg p-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
            </div>
            <div class="mt-2">
                <label class="block text-sm font-medium text-gray-700">Perihal</label>
                <input type="text" x-model="form.perihal" class="w-full border rounded-lg p-2 focus:ring-blue-500 focus:border-blue-500">
            </div>

            <hr class="my-6 border-t-2 border-gray-200">

            <!-- Rincian Dinamis -->
            <div class="mt-4">
                <div class="flex justify-between items-center mb-2">
                    <label class="block text-sm font-medium text-gray-700">📋 Rincian Pembayaran</label>
                    <button type="button" @click="addColumn" class="text-xs bg-gray-100 hover:bg-gray-200 px-3 py-1 rounded transition">➕ Tambah Kolom</button>
                </div>
                <div class="overflow-x-auto border rounded-lg">
                    <table class="min-w-full text-sm table-fixed">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="p-2 w-10">No</th>
                                <th class="p-2">
                                    <input type="text" x-model="keteranganLabel" class="w-full text-center border-none bg-transparent focus:ring-0 font-medium" placeholder="Keterangan">
                                </th>
                                <template x-for="(col, idx) in dynamicCols" :key="idx">
                                    <th class="p-2">
                                        <input type="text" x-model="col.name" class="w-full text-center border-none bg-transparent focus:ring-0" placeholder="Kolom">
                                        <button @click="removeColumn(idx)" class="text-red-500 ml-1 hover:text-red-700">✖</button>
                                    </th>
                                </template>
                                <th class="p-2 w-36 text-right">Tagihan (Rp)</th>
                                <th class="p-2 w-8"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="(row, idx) in rows" :key="idx">
                                <tr>
                                    <td class="p-2 text-center text-gray-500" x-text="idx + 1"></td>
                                    <td class="p-2"><input type="text" x-model="row.keterangan" class="w-full p-1 border rounded" :placeholder="keteranganLabel"></td>
                                    <template x-for="(col, cidx) in dynamicCols" :key="cidx">
                                        <td class="p-2"><input type="text" x-model="row.dynamic[cidx]" class="w-full p-1 border rounded" :placeholder="col.name"></td>
                                    </template>
                                    <td class="p-2"><input type="text" inputmode="decimal" x-model="row.tagihanDisplay" @input="onTagihanInput(row, $event)" @blur="row.tagihanDisplay = formatRupiah(row.tagihan)" class="w-full p-1 border rounded text-right" placeholder="0"></td>
                                    <td class="p-2 text-center"><button @click="removeRow(idx)" class="text-red-500 hover:text-red-700">✖</button></td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
                <button @click="addRow" class="mt-2 text-sm bg-gray-100 hover:bg-gray-200 px-3 py-1 rounded w-full transition">+ Tambah Baris</button>
                <div class="text-right font-bold mt-2" x-text="'Total: Rp ' + formatRupiah(total)"></div>
            </div>

            <!-- Paragraf Pembuka -->
            <div class="mt-4">
                <label class="block text-sm font-medium text-gray-700">Paragraf Pembuka <span class="text-xs text-gray-400 font-normal">(opsional — kosongkan untuk pakai kalimat baku)</span></label>
                <textarea x-model="form.paragraf_pembuka" rows="2" class="w-full border rounded-lg p-2 text-sm" :placeholder="defaultParagrafPembuka()"></textarea>
                <p class="text-xs text-gray-400 mt-1">Kalau dikosongkan, memo pakai kalimat baku: "<span x-text="defaultParagrafPembuka()"></span>". Isi field ini kalau memo Anda butuh kalimat pembuka yang berbeda (mis. memo pemberitahuan, bukan permintaan dana).</p>
            </div>

            <!-- Instruksi & Rekening -->
            <div class="mt-4">
                <label class="block text-sm font-medium text-gray-700">Instruksi</label>
                <textarea x-model="form.instruksi" rows="2" class="w-full border rounded-lg p-2"></textarea>
            </div>
            <div class="grid grid-cols-3 gap-2 mt-2">
                <label class="col-span-3 flex items-center gap-2 text-sm text-gray-700 mb-1">
                    <input type="checkbox" x-model="form.sertakan_rekening" class="rounded">
                    Sertakan blok Rekening Tujuan di memo ini
                </label>
                <template x-if="form.sertakan_rekening">
                    <div><label class="text-xs text-gray-600">Bank</label><input x-model="form.bank" class="w-full border rounded p-1"></div>
                </template>
                <template x-if="form.sertakan_rekening">
                    <div><label class="text-xs text-gray-600">Atas Nama</label><input x-model="form.atas_nama" class="w-full border rounded p-1"></div>
                </template>
                <template x-if="form.sertakan_rekening">
                    <div><label class="text-xs text-gray-600">No Rek</label><input x-model="form.no_rek" class="w-full border rounded p-1"></div>
                </template>
            </div>

            <!-- Penandatangan (otomatis dari data admin tim) -->
            <div class="grid grid-cols-2 gap-2 mt-2">
                <div>
                    <label class="text-xs text-gray-600">Penandatangan <span class="text-gray-400">(otomatis)</span></label>
                    <input :value="signer.penandatangan || '-'" readonly class="w-full border rounded p-1 bg-gray-100 text-gray-600 cursor-not-allowed">
                </div>
                <div>
                    <label class="text-xs text-gray-600">Jabatan <span class="text-gray-400">(otomatis)</span></label>
                    <input :value="signer.jabatan || '-'" readonly class="w-full border rounded p-1 bg-gray-100 text-gray-600 cursor-not-allowed">
                </div>
            </div>
            @if(empty($signer['jabatan']))
                <p class="text-xs text-amber-600 mt-1">⚠️ Jabatan admin belum diatur. Hubungi superadmin untuk melengkapi jabatan di menu Tim.</p>
            @endif

            <!-- Lampiran -->
            <div class="mt-4">
                <label class="block text-sm font-medium text-gray-700">📎 Lampiran (PDF/Gambar)</label>
                <input type="file" multiple accept=".pdf,.jpg,.jpeg,.png" @change="handleAttachments" class="w-full text-sm">
                <div x-show="attachments.length" class="text-xs text-gray-500 mt-1" x-text="attachments.length + ' file baru siap diupload'"></div>

                @if(isset($memo) && $memo->attachments->count())
                <ul class="mt-2 space-y-1">
                    @foreach($memo->attachments as $att)
                    <li class="flex justify-between items-center text-xs bg-gray-50 rounded p-2">
                        <a href="{{ Storage::url($att->file_path) }}" target="_blank" class="text-blue-600 flex items-center gap-1">
                            <i class="fa {{ str_contains($att->mime_type,'pdf') ? 'fa-file-pdf' : 'fa-file-image' }}"></i>
                            {{ $att->original_name }}
                        </a>
                        <form action="{{ route('memos.attachments.destroy', $att) }}" method="POST" onsubmit="return confirm('Hapus lampiran ini?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-red-500 hover:text-red-700"><i class="fas fa-trash-alt"></i></button>
                        </form>
                    </li>
                    @endforeach
                </ul>
                @endif
            </div>

            <!-- Tombol Aksi -->
            <div class="flex gap-3 mt-6">
                <button @click="saveMemo('draft')" class="flex-1 bg-gray-200 text-gray-800 py-2 rounded-lg hover:bg-gray-300 transition">💾 Simpan Draft</button>
                <button @click="saveMemo('submitted')" class="flex-1 bg-blue-600 text-white py-2 rounded-lg hover:bg-blue-700 transition">✅ Simpan & Submit</button>
            </div>
            <p class="text-xs text-gray-400 text-center mt-3">Draft belum mendapat nomor memo. Nomor otomatis dibuat saat memo disubmit. Draft akan otomatis dihapus setelah 24 jam bila tidak diperbarui.</p>
        </div>

        <!-- Preview Panel -->
        <div id="printMemoArea" class="bg-white rounded-xl shadow-sm p-5 sticky top-6">
            <div class="flex justify-between items-center border-b pb-2 mb-4">
                <h2 class="text-xl font-bold">📄 Preview Memo</h2>
                <button id="printPreviewBtn" class="bg-gray-800 text-white px-3 py-1 rounded text-sm no-print hover:bg-gray-700 transition">🖨️ Cetak</button>
            </div>
            <div x-html="previewHtml" class="font-serif text-sm preview-content"></div>
        </div>
    </div>

    <!-- Tanda Informasi Mengambang -->
    <div class="fixed bottom-6 right-6 z-50 no-print" x-data="{ infoOpen: false }">
        <button @click="infoOpen = !infoOpen"
                class="w-12 h-12 rounded-full bg-blue-600 text-white shadow-lg flex items-center justify-center hover:bg-blue-700 transition text-lg font-bold"
                title="Info & Cara Pakai">
            <span x-show="!infoOpen">ℹ️</span>
            <span x-show="infoOpen">✖</span>
        </button>

        <div x-show="infoOpen"
             x-transition
             @click.away="infoOpen = false"
             class="absolute bottom-16 right-0 w-80 bg-white rounded-xl shadow-2xl border p-4 text-sm text-gray-700"
             style="display: none;">
            <h3 class="font-bold text-gray-800 mb-2">ℹ️ Info &amp; Cara Pakai</h3>
            <p class="mb-2">
                <strong>Keterangan</strong>: kolom ini diisi dengan nama/deskripsi tiap item rincian
                (misalnya "Sewa Kendaraan" atau "Konsumsi Rapat"). Label kolom ini bisa diganti
                sesuai kebutuhan — cukup klik langsung teks <em>"Keterangan"</em> di header tabel
                lalu ketik nama baru.
            </p>
            <p class="mb-2">
                <strong>Tambah Kolom</strong>: klik tombol "➕ Tambah Kolom" untuk menambah kolom
                data lain di tabel rincian (contoh: Tanggal, Qty, Satuan). Nama kolom baru juga
                bisa diubah dengan mengklik langsung teks di headernya.
            </p>
            <p>
                Klik ikon ✖ pada header untuk menghapus kolom tambahan yang tidak diperlukan.
                Perubahan pada kolom akan langsung terlihat di panel Preview di sebelah kanan.
            </p>
            <p>
                <strong>Tagihan</strong>: ketik langsung dengan format Indonesia (titik ribuan, koma
                desimal). Kalau ada sen/desimal, selalu ketik 2 digit di belakang koma supaya gak
                ambigu — contoh <em>34,30</em> (tiga puluh sen), BUKAN <em>34,3</em> yang menurut
                aturan desimal berarti sama juga dengan 34,30 (bukan 3 sen).
            </p>
        </div>
    </div>
</div>

<script>
@php
    // @json(...) Blade directive memotong argumennya pakai explode(',', ...) untuk parameter
    // opsional (json options/depth). Kalau argumennya array literal dgn >1 key (banyak koma
    // di dalamnya), argumen ke-potong di tengah & hasil compile jadi rusak. Makanya di sini
    // array-nya dihitung dulu ke variabel PHP biasa, baru di-@json()-kan (tanpa koma di dalam).
    $rowsForJs = isset($memo)
        ? $memo->items->map(fn($i) => [
            'keterangan' => $i->nama,
            'dynamic' => array_values($i->dynamic_columns ?? []),
            'tagihan' => (float) $i->tagihan,
            'tagihanDisplay' => number_format((float) $i->tagihan, (float) $i->tagihan == floor((float) $i->tagihan) ? 0 : 2, ',', '.'),
        ])->values()
        : [];

    $dynamicColsForJs = isset($memo)
        ? collect($memo->dynamic_columns_definition ?? [])->map(fn($c) => ['name' => $c])->values()
        : [];
@endphp
function memoCreator() {
    return {
        memoId: {{ isset($memo) ? $memo->id : 'null' }},
        signer: @json($signer),
        form: {
            kepada: @json(isset($memo) ? $memo->kepada : ''),
            dari: @json(isset($memo) ? $memo->dari : ''),
            perihal: @json(isset($memo) ? $memo->perihal : ''),
            instruksi: @json(isset($memo) ? $memo->instruksi : ''),
            bank: @json(isset($memo) ? $memo->bank : ''),
            atas_nama: @json(isset($memo) ? $memo->atas_nama : ''),
            no_rek: @json(isset($memo) ? $memo->no_rek : ''),
            sertakan_rekening: @json(isset($memo) ? (bool) $memo->sertakan_rekening : true),
            paragraf_pembuka: @json(isset($memo) ? $memo->paragraf_pembuka : '')
        },
        rows: @json($rowsForJs),
        dynamicCols: @json($dynamicColsForJs),
        keteranganLabel: @json(isset($memo) && !empty($memo->keterangan_label) ? $memo->keterangan_label : 'Keterangan'),
        total: 0,
        attachments: [],
        previewHtml: '',
        templates: [],
        selectedTemplateId: '',
        init() {
            if (this.rows.length === 0) this.addRow();
            this.calculateTotal();
            this.generatePreview();
            this.setupPrint();
            this.fetchTemplates();
        },
        async fetchTemplates() {
            try {
                const res = await fetch('{{ route("memo-templates.index") }}');
                const data = await res.json();
                if (data.success) this.templates = data.templates;
            } catch (e) { /* diam saja, dropdown cukup kosong */ }
        },
        loadTemplate() {
            const t = this.templates.find(t => t.id == this.selectedTemplateId);
            if (!t) return;
            this.form.kepada = t.kepada || '';
            this.form.dari = t.dari || '';
            this.form.perihal = t.perihal || '';
            this.form.instruksi = t.instruksi || '';
            this.form.bank = t.bank || '';
            this.form.atas_nama = t.atas_nama || '';
            this.form.no_rek = t.no_rek || '';
            this.form.sertakan_rekening = t.sertakan_rekening !== undefined ? !!t.sertakan_rekening : true;
            this.keteranganLabel = t.keterangan_label || 'Keterangan';
            const cols = t.dynamic_columns_definition || [];
            this.dynamicCols = cols.map(name => ({ name }));
            // Muat rincian & nominal apa adanya dari template. User tinggal edit
            // bagian mana saja yang perlu diganti (item, rekening, atau keduanya).
            const items = t.items || [];
            this.rows = items.length
                ? items.map(i => ({
                    keterangan: i.keterangan || '',
                    dynamic: Array.isArray(i.dynamic_columns) ? i.dynamic_columns : Array(cols.length).fill(''),
                    tagihan: parseFloat(i.tagihan) || 0,
                    tagihanDisplay: this.formatRupiah(parseFloat(i.tagihan) || 0)
                }))
                : [{ keterangan: '', dynamic: Array(cols.length).fill(''), tagihan: 0, tagihanDisplay: '' }];
            this.form.paragraf_pembuka = t.paragraf_pembuka || '';
            this.calculateTotal();
        },
        buildSnapshotFormData(name) {
            let fd = new FormData();
            fd.append('name', name);
            fd.append('kepada', this.form.kepada || '');
            fd.append('dari', this.form.dari || '');
            fd.append('perihal', this.form.perihal || '');
            fd.append('instruksi', this.form.instruksi || '');
            fd.append('bank', this.form.bank || '');
            fd.append('atas_nama', this.form.atas_nama || '');
            fd.append('no_rek', this.form.no_rek || '');
            fd.append('sertakan_rekening', this.form.sertakan_rekening ? '1' : '0');
            fd.append('paragraf_pembuka', this.form.paragraf_pembuka || '');
            fd.append('keterangan_label', this.keteranganLabel || '');
            fd.append('dynamic_columns', JSON.stringify(this.dynamicCols.map(c => c.name)));
            fd.append('items', JSON.stringify(this.rows.map(r => ({
                keterangan: r.keterangan,
                dynamic_columns: r.dynamic,
                tagihan: r.tagihan
            }))));
            return fd;
        },
        async saveAsTemplate() {
            if (this.templates.length >= 15) {
                alert('Maksimal 15 template per tim. Hapus salah satu dulu untuk menyimpan yang baru.');
                return;
            }
            const name = prompt('Nama template ini (mis. "Claim Pengobatan Karyawan"):');
            if (!name) return;

            let res = await fetch('{{ route("memo-templates.store") }}', {
                method: 'POST',
                body: this.buildSnapshotFormData(name),
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
            });
            let data = await res.json();
            if (data.success) {
                await this.fetchTemplates();
                this.selectedTemplateId = data.template.id;
            } else {
                alert('Error: ' + (data.message || 'Gagal menyimpan template'));
            }
        },
        async updateTemplate() {
            if (!this.selectedTemplateId) return;
            const current = this.templates.find(t => t.id == this.selectedTemplateId);
            const name = prompt('Nama template:', current ? current.name : '');
            if (!name) return;
            if (!confirm('Timpa template "' + name + '" dengan isian form saat ini? Ini akan mengubah template untuk semua anggota tim.')) return;

            let fd = this.buildSnapshotFormData(name);
            fd.append('_method', 'PUT');
            let res = await fetch(`/memo-templates/${this.selectedTemplateId}`, {
                method: 'POST',
                body: fd,
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
            });
            let data = await res.json();
            if (data.success) {
                await this.fetchTemplates();
            } else {
                alert('Error: ' + (data.message || 'Gagal memperbarui template'));
            }
        },
        async deleteTemplate() {
            if (!this.selectedTemplateId) return;
            if (!confirm('Hapus template ini?')) return;
            let res = await fetch(`/memo-templates/${this.selectedTemplateId}`, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                body: (() => { const fd = new FormData(); fd.append('_method', 'DELETE'); return fd; })()
            });
            let data = await res.json();
            if (data.success) {
                this.selectedTemplateId = '';
                await this.fetchTemplates();
            } else {
                alert('Error: ' + (data.message || 'Gagal menghapus template'));
            }
        },
        calculateTotal() {
            this.total = this.rows.reduce((s, r) => s + (parseFloat(r.tagihan) || 0), 0);
            this.generatePreview();
        },
        formatRupiah(num) {
            if (isNaN(num) || num === null || num === undefined || num === '') num = 0;
            return new Intl.NumberFormat('id-ID', { minimumFractionDigits: 0, maximumFractionDigits: 2 }).format(num);
        },
        // User mengetik langsung di kolom Tagihan (format Indonesia: titik ribuan, koma desimal).
        // Selama fokus di field, biarkan apa adanya (cuma dibatasi angka & 1 koma) supaya enak diketik;
        // begitu blur, dirapikan otomatis lewat formatRupiah().
        onTagihanInput(row, event) {
            let raw = event.target.value.replace(/[^0-9,]/g, '');
            const firstComma = raw.indexOf(',');
            if (firstComma !== -1) {
                raw = raw.slice(0, firstComma + 1) + raw.slice(firstComma + 1).replace(/,/g, '');
            }
            row.tagihanDisplay = raw;
            row.tagihan = parseFloat(raw.replace(',', '.')) || 0;
            this.calculateTotal();
        },
        defaultParagrafPembuka() {
            const perihal = this.form.perihal || '(perihal)';
            return `Mohon disiapkan dana sebesar Rp ${this.formatRupiah(this.total)} untuk ${perihal} dengan rincian:`;
        },
        addRow() {
            this.rows.push({
                keterangan: '',
                dynamic: Array(this.dynamicCols.length).fill(''),
                tagihan: 0,
                tagihanDisplay: ''
            });
            this.calculateTotal();
        },
        removeRow(idx) {
            if (this.rows.length > 1) {
                this.rows.splice(idx, 1);
                this.calculateTotal();
            } else {
                alert('Minimal harus ada satu baris rincian.');
            }
        },
        addColumn() {
            this.dynamicCols.push({ name: 'Kolom Baru' });
            this.rows.forEach(r => r.dynamic.push(''));
            this.calculateTotal();
        },
        removeColumn(idx) {
            this.dynamicCols.splice(idx, 1);
            this.rows.forEach(r => r.dynamic.splice(idx, 1));
            this.calculateTotal();
        },
        setupPrint() {
            const printBtn = document.getElementById('printPreviewBtn');
            if (printBtn) {
                printBtn.addEventListener('click', () => {
                    const printWindow = window.open('', '_blank');
                    printWindow.document.write(`
                        <html><head><title>Cetak Memo - ${this.form.perihal || 'Draft'} by GA Portal</title>
                        <style>
                            body { font-family: 'Times New Roman', serif; padding: 20px; margin: 0; background: white; }
                            .memo-container { max-width: 800px; margin: 0 auto; }
                            table { width: 100%; border-collapse: collapse; margin: 10px 0; }
                            th, td { border: 1px solid #000; padding: 6px; text-align: left; vertical-align: top; }
                            .text-right { text-align: right; }
                            .font-bold { font-weight: bold; }
                            .border-l-4 { border-left: 4px solid #2563eb; padding-left: 12px; }
                            .text-center { text-align: center; }
                            h2 { margin-top: 0; }
                            .print-footer { margin-top: 24px; padding-top: 8px; border-top: 1px solid #ccc; text-align: center; font-size: 11px; color: #555; }
                        </style>
                        </head><body><div class="memo-container">${this.previewHtml}<div class="print-footer">Cetak Memo - ${this.form.perihal || 'Draft'} by GA Portal</div></div></body></html>
                    `);
                    printWindow.document.close();
                    printWindow.print();
                });
            }
        },
        async saveMemo(status) {
            const validRows = this.rows.filter(r => r.keterangan.trim() !== '' && parseFloat(r.tagihan) > 0);
            if (validRows.length === 0) {
                alert('Harap isi minimal satu baris rincian dengan keterangan dan tagihan yang valid.');
                return;
            }
            let payload = {
                ...this.form,
                status: status,
                keteranganLabel: this.keteranganLabel,
                dynamicColumns: this.dynamicCols.map(c => c.name),
                items: this.rows.map(r => ({
                    keterangan: r.keterangan,
                    dynamic_columns: r.dynamic,
                    tagihan: r.tagihan
                }))
            };
            let fd = new FormData();
            for (let k in payload) {
                if (k === 'items' || k === 'dynamicColumns') {
                    fd.append(k, JSON.stringify(payload[k]));
                } else {
                    fd.append(k, payload[k]);
                }
            }
            for (let f of this.attachments) fd.append('attachments[]', f);

            let url = this.memoId ? `/memos/${this.memoId}` : '{{ route("memos.store") }}';
            if (this.memoId) fd.append('_method', 'PUT');

            let res = await fetch(url, {
                method: 'POST',
                body: fd,
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
            });
            let data = await res.json();
            if (data.success) {
                window.location.href = '/memos/' + data.memo_id;
            } else {
                alert('Error: ' + (data.message || 'Gagal menyimpan memo'));
            }
        },
        handleAttachments(e) {
            this.attachments = Array.from(e.target.files);
        },
        async generatePreview() {
            let dynamicCols = this.dynamicCols.map(c => c.name);
            let itemsHtml = '';
            itemsHtml += '<table class="w-full border-collapse border"><thead><tr>';
            itemsHtml += `<th>No</th><th>${this.escapeHtml(this.keteranganLabel) || 'Keterangan'}</th>`;
            dynamicCols.forEach(c => itemsHtml += `<th>${this.escapeHtml(c)}</th>`);
            itemsHtml += '<th>Tagihan</th></tr></thead><tbody>';
            if (this.rows.length === 0 || (this.rows.length === 1 && this.rows[0].keterangan === '' && this.rows[0].tagihan === 0)) {
                let colspan = 2 + dynamicCols.length + 1;
                itemsHtml += `<tr><td colspan="${colspan}" class="text-center text-gray-400">Belum ada data</td></tr>`;
            } else {
                this.rows.forEach((row, idx) => {
                    itemsHtml += `<tr><td class="text-center">${idx + 1}</td><td>${this.escapeHtml(row.keterangan)}</td>`;
                    for (let i = 0; i < dynamicCols.length; i++) {
                        let val = row.dynamic[i] || '';
                        itemsHtml += `<td>${this.escapeHtml(val)}</td>`;
                    }
                    itemsHtml += `<td class="text-right">Rp ${this.formatRupiah(row.tagihan)}</td></tr>`;
                });
            }
            let colspanTotal = 2 + dynamicCols.length;
            itemsHtml += `<tr class="font-bold"><td colspan="${colspanTotal}" class="text-right">TOTAL</td><td class="text-right">Rp ${this.formatRupiah(this.total)}</td></tr>`;
            itemsHtml += '</tbody></table>';

            // Ambil terbilang dari server jika total > 0
            let terbilangText = '';
            if (this.total > 0) {
                try {
                    const resp = await fetch(`/api/terbilang/${this.total.toFixed(2)}`);
                    const data = await resp.json();
                    terbilangText = data.terbilang;
                } catch (e) {
                    terbilangText = '';
                }
            }

            const tgl = new Date().toLocaleDateString('id-ID');
            this.previewHtml = `
                <div class="text-right text-sm">${tgl}<br>No. (Akan digenerate sistem saat submit)</div>
                <h2 class="text-center text-xl font-bold my-3">MEMORANDUM</h2>
                <p><strong>Kepada</strong> : ${this.escapeHtml(this.form.kepada) || '-'}</p>
                <p><strong>Dari</strong> : ${this.escapeHtml(this.form.dari) || '-'}</p>
                <p><strong>Perihal</strong> : ${this.escapeHtml(this.form.perihal) || '-'}</p>
                <hr style="margin: 16px 0; border: none; border-top: 2px solid #333;">
                <p>${(this.form.paragraf_pembuka && this.form.paragraf_pembuka.trim() !== '') ? this.escapeHtml(this.form.paragraf_pembuka).replace(/\n/g, '<br>') : `Mohon disiapkan dana sebesar <strong>Rp ${this.formatRupiah(this.total)}</strong> ${terbilangText ? '('+terbilangText+' rupiah)' : ''} untuk ${this.escapeHtml(this.form.perihal) || '-'} dengan rincian:`}</p>
                ${itemsHtml}
                <p>${this.escapeHtml(this.form.instruksi) || '-'}</p>
                ${this.form.sertakan_rekening ? `<div class="border-l-4 border-blue-600 pl-3 my-3"><strong>Rekening Tujuan</strong><br>Bank : ${this.escapeHtml(this.form.bank) || '-'}<br>Atas Nama : ${this.escapeHtml(this.form.atas_nama) || '-'}<br>No Rek : ${this.escapeHtml(this.form.no_rek) || '-'}</div>` : ''}
                <p class="mt-6">Hormat kami,<br><br>${this.escapeHtml(this.signer.penandatangan) || '-'}<br>${this.escapeHtml(this.signer.jabatan) || '-'}</p>
            `;
        },
        escapeHtml(str) {
            if (!str) return '';
            return String(str).replace(/[&<>]/g, m => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;' })[m]);
        }
    }
}
</script>
@endsection
