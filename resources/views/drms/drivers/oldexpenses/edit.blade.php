@extends('layouts.app_car_drive_sidebar')

@section('content')
<div class="container mx-auto max-w-lg px-4 py-6">
    <div class="flex items-center justify-between mb-4">
        <h1 class="text-2xl font-bold">✏️ Edit Entri Pengeluaran</h1>
        <a href="{{ route('drms.driver.expenses.index') }}" class="text-sm text-blue-600 hover:underline">← Kembali ke Laporan</a>
    </div>

    <div class="bg-yellow-50 border border-yellow-200 text-yellow-700 text-xs px-4 py-2 rounded mb-4">
        Entri ini bisa diedit sampai <strong>{{ $expense->edit_deadline->format('d M Y') }}</strong>
        ({{ \App\Models\Drms\ExpenseReport::EDITABLE_DAYS }} hari sejak pertama diisi). Setelah itu terkunci otomatis.
        Entri tidak bisa dihapus.
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

    <form action="{{ route('drms.driver.expenses.update', $expense->id) }}" method="POST" class="bg-white p-4 rounded-lg shadow-sm border space-y-4">
        @csrf
        @method('PUT')

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Perjalanan Terkait</label>
            <p class="text-sm text-gray-600 bg-gray-50 border rounded px-3 py-2">
                @if($expense->request)
                    #{{ $expense->request->request_no }} — {{ \Carbon\Carbon::parse($expense->request->usage_date)->format('d M Y') }} — {{ $expense->request->destination }}
                @else
                    -
                @endif
            </p>
            <p class="text-xs text-gray-400 mt-1">Kaitan perjalanan tidak bisa diubah.</p>
        </div>

        <div>
            <label for="category" class="block text-sm font-medium text-gray-700 mb-1">Kategori</label>
            <select name="category" id="category" class="w-full border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                @foreach(\App\Models\Drms\ExpenseReport::CATEGORIES as $key => $label)
                    <option value="{{ $key }}" {{ old('category', $expense->category) == $key ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label for="report_date" class="block text-sm font-medium text-gray-700 mb-1">Tanggal</label>
            <input type="date" name="report_date" id="report_date" value="{{ old('report_date', $expense->report_date->format('Y-m-d')) }}" class="w-full border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" required>
        </div>

        <div>
            <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Keterangan</label>
            <input type="text" name="description" id="description" value="{{ old('description', $expense->description) }}" class="w-full border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Opsional">
        </div>

        <div>
            <label for="amount" class="block text-sm font-medium text-gray-700 mb-1">Nominal (Rp)</label>
            <input type="number" min="0" step="1" name="amount" id="amount" value="{{ old('amount', $expense->amount) }}" class="w-full border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" required>
        </div>

        <div class="flex justify-end gap-2 pt-2">
            <a href="{{ route('drms.driver.expenses.index') }}" class="px-4 py-2 bg-gray-300 rounded hover:bg-gray-400">Batal</a>
            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Simpan Perubahan</button>
        </div>
    </form>
</div>
@endsection
