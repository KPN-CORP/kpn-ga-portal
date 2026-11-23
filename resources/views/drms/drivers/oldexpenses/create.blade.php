@extends('layouts.app_car_drive_sidebar')

@section('content')
<div class="container mx-auto max-w-3xl px-4 py-6">
    <div class="flex items-center justify-between mb-4">
        <h1 class="text-2xl font-bold">🧾 Input Laporan Pengeluaran</h1>
        <a href="{{ route('drms.driver.expenses.index') }}" class="text-sm text-blue-600 hover:underline">← Kembali ke Laporan</a>
    </div>

    @if($errors->any())
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            <ul class="list-disc pl-5">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('drms.driver.expenses.store') }}" method="POST" id="expenseForm">
        @csrf

        <div class="bg-white p-4 rounded-lg shadow-sm border mb-4">
            <label for="request_id" class="block text-sm font-medium text-gray-700 mb-1">Kaitkan dengan Perjalanan <span class="text-red-500">*</span></label>
            <select name="request_id" id="request_id" class="w-full border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                <option value="">-- Pilih perjalanan --</option>
                @foreach($requests as $r)
                    <option value="{{ $r->id }}" {{ old('request_id') == $r->id ? 'selected' : '' }}>
                        #{{ $r->request_no }} — {{ \Carbon\Carbon::parse($r->usage_date)->format('d M Y') }} — {{ $r->destination }}
                    </option>
                @endforeach
            </select>
            <p class="text-xs text-gray-400 mt-1">Laporan pengeluaran wajib dikaitkan dengan salah satu perjalanan Anda.</p>
        </div>

        @php
            $categories = [
                'toll'       => ['label' => 'Toll', 'icon' => '🛣️'],
                'parkir'     => ['label' => 'Parkir', 'icon' => '🅿️'],
                'bbm'        => ['label' => 'BBM', 'icon' => '⛽'],
                'cuci_mobil' => ['label' => 'Cuci Mobil', 'icon' => '🚿'],
            ];
        @endphp

        @foreach($categories as $catKey => $cat)
        <div class="bg-white p-4 rounded-lg shadow-sm border mb-4" data-category-block="{{ $catKey }}">
            <div class="flex items-center justify-between mb-3">
                <h2 class="font-semibold text-gray-700">{{ $cat['icon'] }} {{ $cat['label'] }}</h2>
                <button type="button" class="text-xs bg-blue-50 text-blue-700 px-3 py-1 rounded-full font-semibold hover:bg-blue-100 btn-add-row" data-category="{{ $catKey }}">
                    ++ Tambah {{ $cat['label'] }}
                </button>
            </div>
            <div class="space-y-2 rows-container" id="rows-{{ $catKey }}">
                {{-- baris pertama dimulai secara default --}}
            </div>
            <p class="text-xs text-gray-400 mt-2 empty-hint" id="empty-hint-{{ $catKey }}">Belum ada entri {{ $cat['label'] }}. Klik "++ Tambah {{ $cat['label'] }}" untuk menambah.</p>
        </div>
        @endforeach

        <div class="bg-white p-4 rounded-lg shadow-sm border mb-6 flex items-center justify-between">
            <span class="font-semibold text-gray-700">Total Keseluruhan</span>
            <span class="text-xl font-bold text-blue-600" id="grand-total">Rp 0</span>
        </div>

        <div class="flex justify-end gap-2">
            <a href="{{ route('drms.driver.expenses.index') }}" class="px-4 py-2 bg-gray-300 rounded hover:bg-gray-400">Batal</a>
            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Simpan Laporan</button>
        </div>
    </form>
</div>

{{-- Template baris (disembunyikan, di-clone lewat JS) --}}
<template id="row-template">
    <div class="expense-row flex flex-wrap items-center gap-2 border rounded-lg p-2">
        <input type="date" class="row-date border rounded px-2 py-1.5 text-sm flex-1 min-w-[130px]" value="{{ now()->format('Y-m-d') }}" required>
        <input type="text" class="row-description border rounded px-2 py-1.5 text-sm flex-[2] min-w-[150px]" placeholder="Keterangan (opsional)">
        <input type="number" min="0" step="1" class="row-amount border rounded px-2 py-1.5 text-sm w-32" placeholder="Nominal (Rp)" required>
        <button type="button" class="btn-remove-row text-red-500 hover:text-red-700 text-sm px-2">✕</button>
    </div>
</template>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('expenseForm');
    const template = document.getElementById('row-template');
    const grandTotalEl = document.getElementById('grand-total');

    function formatRupiah(n) {
        return 'Rp ' + Number(n || 0).toLocaleString('id-ID');
    }

    function recalcTotal() {
        let total = 0;
        document.querySelectorAll('.row-amount').forEach(function (input) {
            total += parseFloat(input.value) || 0;
        });
        grandTotalEl.textContent = formatRupiah(total);
    }

    function toggleEmptyHint(category) {
        const container = document.getElementById('rows-' + category);
        const hint = document.getElementById('empty-hint-' + category);
        hint.style.display = container.children.length ? 'none' : 'block';
    }

    function addRow(category) {
        const container = document.getElementById('rows-' + category);
        const clone = template.content.cloneNode(true);
        container.appendChild(clone);
        toggleEmptyHint(category);
        recalcTotal();
    }

    document.querySelectorAll('.btn-add-row').forEach(function (btn) {
        btn.addEventListener('click', function () {
            addRow(btn.dataset.category);
        });
    });

    document.addEventListener('click', function (e) {
        if (e.target.classList.contains('btn-remove-row')) {
            const row = e.target.closest('.expense-row');
            const container = row.closest('.rows-container');
            row.remove();
            toggleEmptyHint(container.id.replace('rows-', ''));
            recalcTotal();
        }
    });

    document.addEventListener('input', function (e) {
        if (e.target.classList.contains('row-amount')) {
            recalcTotal();
        }
    });

    // Mulai dengan 1 baris kosong per kategori supaya driver langsung bisa isi.
    ['toll', 'parkir', 'bbm', 'cuci_mobil'].forEach(addRow);

    // Sebelum submit, ubah baris-baris di DOM menjadi input hidden items[category][i][field]
    form.addEventListener('submit', function (e) {
        document.querySelectorAll('.rows-container').forEach(function (container) {
            const category = container.id.replace('rows-', '');
            container.querySelectorAll('.expense-row').forEach(function (row, i) {
                const date = row.querySelector('.row-date').value;
                const description = row.querySelector('.row-description').value;
                const amount = row.querySelector('.row-amount').value;

                if (!amount) return; // baris kosong tidak dikirim

                ['date', 'description', 'amount'].forEach(function (field) {
                    const hidden = document.createElement('input');
                    hidden.type = 'hidden';
                    hidden.name = `items[${category}][${i}][${field}]`;
                    hidden.value = field === 'date' ? date : (field === 'description' ? description : amount);
                    form.appendChild(hidden);
                });
            });
        });
    });
});
</script>
@endsection
