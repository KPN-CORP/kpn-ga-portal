@extends('layouts.app_memos')
@section('title', 'Buat Memo Baru')
@section('content')
<div x-data="memoCreator()" x-init="init()" class="w-full px-2 md:px-4">
    <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
        <!-- Panel Form -->
        <div class="bg-white rounded-xl shadow-sm p-5">
            <h2 class="text-xl font-bold mb-4">📝 Buat E-Memo + AI</h2>

            <!-- Upload AI -->
            <div @click="triggerUpload" class="border-2 border-dashed rounded-lg p-6 text-center cursor-pointer hover:border-blue-400 transition">
                <input type="file" id="fileInput" @change="processFile" accept=".xlsx,.xls,.pdf,.jpg,.jpeg,.png" hidden>
                <p>📂 Upload Excel, PDF, atau Gambar<br><span class="text-xs">AI akan ekstrak nama & tagihan</span></p>
                <div x-show="uploadProgress" x-text="uploadProgress" class="text-sm text-blue-600 mt-2"></div>
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

            <!-- Rincian Dinamis -->
            <div class="mt-4">
                <div class="flex justify-between items-center mb-2">
                    <label class="block text-sm font-medium text-gray-700">📋 Rincian Pembayaran</label>
                    <button type="button" @click="addColumn" class="text-xs bg-gray-100 hover:bg-gray-200 px-3 py-1 rounded transition">➕ Tambah Kolom</button>
                </div>
                <div class="overflow-x-auto border rounded-lg">
                    <table class="min-w-full text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="p-2">Nama</th>
                                <th class="p-2">PT/Unit</th>
                                <template x-for="(col, idx) in dynamicCols" :key="idx">
                                    <th class="p-2">
                                        <input type="text" x-model="col.name" class="w-24 text-center border-none bg-transparent focus:ring-0" placeholder="Kolom">
                                        <button @click="removeColumn(idx)" class="text-red-500 ml-1 hover:text-red-700">✖</button>
                                    </th>
                                </template>
                                <th class="p-2">Tagihan (Rp)</th>
                                <th class="p-2"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="(row, idx) in rows" :key="idx">
                                <tr>
                                    <td><input type="text" x-model="row.nama" class="w-full p-1 border rounded" placeholder="Nama"></td>
                                    <td><input type="text" x-model="row.pt_unit" class="w-full p-1 border rounded" placeholder="PT/Unit"></td>
                                    <template x-for="(col, cidx) in dynamicCols" :key="cidx">
                                        <td><input type="text" x-model="row.dynamic[cidx]" class="w-full p-1 border rounded" :placeholder="col.name"></td>
                                    </template>
                                    <td><input type="number" x-model="row.tagihan" @input="calculateTotal" class="w-28 p-1 border rounded" placeholder="0"></td>
                                    <td><button @click="removeRow(idx)" class="text-red-500 hover:text-red-700">✖</button></td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
                <button @click="addRow" class="mt-2 text-sm bg-gray-100 hover:bg-gray-200 px-3 py-1 rounded w-full transition">+ Tambah Baris</button>
                <div class="text-right font-bold mt-2" x-text="'Total: Rp ' + formatRupiah(total)"></div>
            </div>

            <!-- Instruksi & Rekening -->
            <div class="mt-4">
                <label class="block text-sm font-medium text-gray-700">Instruksi</label>
                <textarea x-model="form.instruksi" rows="2" class="w-full border rounded-lg p-2"></textarea>
            </div>
            <div class="grid grid-cols-3 gap-2 mt-2">
                <div><label class="text-xs text-gray-600">Bank</label><input x-model="form.bank" class="w-full border rounded p-1"></div>
                <div><label class="text-xs text-gray-600">Atas Nama</label><input x-model="form.atas_nama" class="w-full border rounded p-1"></div>
                <div><label class="text-xs text-gray-600">No Rek</label><input x-model="form.no_rek" class="w-full border rounded p-1"></div>
            </div>
            <div class="grid grid-cols-2 gap-2 mt-2">
                <div><label class="text-xs text-gray-600">Penandatangan</label><input x-model="form.penandatangan" class="w-full border rounded p-1"></div>
                <div><label class="text-xs text-gray-600">Jabatan</label><input x-model="form.jabatan" class="w-full border rounded p-1"></div>
            </div>

            <!-- Lampiran -->
            <div class="mt-4">
                <label class="block text-sm font-medium text-gray-700">📎 Lampiran (PDF/Gambar)</label>
                <input type="file" multiple @change="handleAttachments" class="w-full text-sm">
                <div x-show="attachments.length" class="text-xs text-gray-500 mt-1" x-text="attachments.length + ' file siap diupload'"></div>
            </div>

            <!-- Tombol Aksi -->
            <div class="flex gap-3 mt-6">
                <button @click="saveMemo('draft')" class="flex-1 bg-gray-200 text-gray-800 py-2 rounded-lg hover:bg-gray-300 transition">💾 Simpan Draft</button>
                <button @click="saveMemo('submitted')" class="flex-1 bg-blue-600 text-white py-2 rounded-lg hover:bg-blue-700 transition">✅ Simpan & Submit</button>
            </div>
            <p class="text-xs text-gray-400 text-center mt-3">Draft akan otomatis dihapus setelah 24 jam</p>
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
</div>

<script>
function memoCreator() {
    return {
        form: {
            kepada: '',
            dari: '',
            perihal: '',
            instruksi: '',
            bank: '',
            atas_nama: '',
            no_rek: '',
            penandatangan: '',
            jabatan: ''
        },
        rows: [],
        dynamicCols: [],
        total: 0,
        attachments: [],
        uploadProgress: '',
        previewHtml: '',
        async init() {
            this.addRow();
            this.calculateTotal();
            await this.generatePreview();
            this.setupPrint();
        },
        calculateTotal() {
            this.total = this.rows.reduce((s, r) => s + (parseFloat(r.tagihan) || 0), 0);
            this.generatePreview();
        },
        formatRupiah(num) {
            if (isNaN(num)) num = 0;
            return new Intl.NumberFormat('id-ID').format(num);
        },
        addRow() {
            this.rows.push({
                nama: '',
                pt_unit: '',
                dynamic: Array(this.dynamicCols.length).fill(''),
                tagihan: 0
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
                        <html><head><title>Cetak Memo</title>
                        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
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
                        </style>
                        </head><body><div class="memo-container">${this.previewHtml}</div></body></html>
                    `);
                    printWindow.document.close();
                    printWindow.print();
                });
            }
        },
        async processFile(e) {
            let file = e.target.files[0];
            if (!file) return;
            this.uploadProgress = 'Memproses AI...';
            let ext = file.name.split('.').pop().toLowerCase();
            let extracted = [];
            try {
                if (ext === 'xlsx' || ext === 'xls') {
                    let data = await file.arrayBuffer();
                    let wb = XLSX.read(data);
                    let sheet = wb.Sheets[wb.SheetNames[0]];
                    let rows = XLSX.utils.sheet_to_json(sheet, { header: 1 });
                    for (let i = 1; i < rows.length; i++) {
                        let nama = rows[i][0] || '';
                        let tagihan = parseFloat(String(rows[i][rows[i].length - 1]).replace(/[^0-9.-]/g, '')) || 0;
                        if (nama && tagihan > 0) extracted.push({ nama, pt_unit: rows[i][1] || '', dynamic: [], tagihan });
                    }
                } else if (ext === 'pdf') {
                    let arrayBuffer = await file.arrayBuffer();
                    let pdf = await pdfjsLib.getDocument({ data: arrayBuffer }).promise;
                    for (let p = 1; p <= pdf.numPages; p++) {
                        let page = await pdf.getPage(p);
                        let text = await page.getTextContent();
                        let pageText = text.items.map(t => t.str).join(' ');
                        let matches = pageText.matchAll(/([A-Za-z\s]+?)(?:Rp\s*)?(\d{1,3}(?:[.,]\d{3})*(?:[.,]\d{2})?)/g);
                        for (let m of matches) {
                            let tagihan = parseFloat(m[2].replace(/\./g, '').replace(',', '.'));
                            if (tagihan > 1000) extracted.push({ nama: m[1].trim(), pt_unit: '', dynamic: [], tagihan });
                        }
                    }
                } else {
                    let { data: { text } } = await Tesseract.recognize(file, 'ind+eng');
                    let lines = text.split('\n');
                    for (let line of lines) {
                        let priceMatch = line.match(/(\d{1,3}(?:[.,]\d{3})*(?:[.,]\d{2})?)/);
                        if (priceMatch) {
                            let tagihan = parseFloat(priceMatch[1].replace(/\./g, '').replace(',', '.'));
                            if (tagihan > 1000) extracted.push({ nama: line.replace(priceMatch[0], '').trim(), pt_unit: '', dynamic: [], tagihan });
                        }
                    }
                }
                if (extracted.length) {
                    this.rows.push(...extracted);
                    this.calculateTotal();
                    this.uploadProgress = `✅ ${extracted.length} baris diekstrak`;
                } else {
                    this.uploadProgress = '⚠️ Tidak ada data terdeteksi';
                }
            } catch (err) {
                this.uploadProgress = '❌ Gagal memproses file';
            }
            setTimeout(() => this.uploadProgress = '', 3000);
        },
        async saveMemo(status) {
            const validRows = this.rows.filter(r => r.nama.trim() !== '' && parseFloat(r.tagihan) > 0);
            if (validRows.length === 0) {
                alert('Harap isi minimal satu baris rincian dengan nama dan tagihan yang valid.');
                return;
            }
            let payload = {
                ...this.form,
                status: status,
                dynamicColumns: this.dynamicCols.map(c => c.name),
                items: this.rows.map(r => ({
                    nama: r.nama,
                    pt_unit: r.pt_unit,
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
            let res = await fetch('{{ route("memos.store") }}', {
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
        triggerUpload() {
            document.getElementById('fileInput').click();
        },
        async generatePreview() {
            let dynamicCols = this.dynamicCols.map(c => c.name);
            let itemsHtml = '';
            itemsHtml += '<table class="w-full border-collapse border"><thead><tr>';
            itemsHtml += '<th>Nama</th><th>PT/Unit</th>';
            dynamicCols.forEach(c => itemsHtml += `<th>${this.escapeHtml(c)}</th>`);
            itemsHtml += '<th>Tagihan</th></tr></thead><tbody>';
            if (this.rows.length === 0 || (this.rows.length === 1 && this.rows[0].nama === '' && this.rows[0].tagihan === 0)) {
                let colspan = 2 + dynamicCols.length + 1;
                itemsHtml += `<tr><td colspan="${colspan}" class="text-center text-gray-400">Belum ada data</td></tr>`;
            } else {
                this.rows.forEach(row => {
                    itemsHtml += `<tr><td>${this.escapeHtml(row.nama)}</td><td>${this.escapeHtml(row.pt_unit)}</td>`;
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
                    const resp = await fetch(`/api/terbilang/${Math.round(this.total)}`);
                    const data = await resp.json();
                    terbilangText = data.terbilang;
                } catch (e) {
                    terbilangText = '';
                }
            }

            const tgl = new Date().toLocaleDateString('id-ID');
            this.previewHtml = `
                <div class="text-right text-sm">${tgl}<br>No. (Akan digenerate sistem)</div>
                <h2 class="text-center text-xl font-bold my-3">MEMORANDUM</h2>
                <p><strong>Kepada</strong> : ${this.escapeHtml(this.form.kepada) || '-'}</p>
                <p><strong>Dari</strong> : ${this.escapeHtml(this.form.dari) || '-'}</p>
                <p><strong>Perihal</strong> : ${this.escapeHtml(this.form.perihal) || '-'}</p>
                <p>Mohon disiapkan dana sebesar <strong>Rp ${this.formatRupiah(this.total)}</strong> ${terbilangText ? '('+terbilangText+' rupiah)' : ''} untuk ${this.escapeHtml(this.form.perihal) || '-'} dengan rincian:</p>
                ${itemsHtml}
                <p>${this.escapeHtml(this.form.instruksi) || '-'}</p>
                <div class="border-l-4 border-blue-600 pl-3 my-3"><strong>Rekening Tujuan</strong><br>Bank : ${this.escapeHtml(this.form.bank) || '-'}<br>Atas Nama : ${this.escapeHtml(this.form.atas_nama) || '-'}<br>No Rek : ${this.escapeHtml(this.form.no_rek) || '-'}</div>
                <p class="mt-6">Hormat kami,<br><br>${this.escapeHtml(this.form.penandatangan) || '-'}<br>${this.escapeHtml(this.form.jabatan) || '-'}</p>
            `;
        },
        escapeHtml(str) {
            if (!str) return '';
            return str.replace(/[&<>]/g, m => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;' })[m]);
        }
    }
}
</script>
@endsection