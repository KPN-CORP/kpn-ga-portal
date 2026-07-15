@extends('layouts.app_car_sidebar')

@section('content')
<div class="container mx-auto max-w-2xl">
    <h1 class="text-2xl font-bold mb-4">Edit Laporan Perbaikan</h1>

    @if($errors->any())
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            <ul class="list-disc pl-5">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    <form action="{{ route('drms.repairs.update', $repair->id) }}" method="POST" class="bg-white p-6 rounded shadow">
        @csrf @method('PUT')

        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700">Kendaraan <span class="text-red-500">*</span></label>
            <select name="vehicle_id" class="w-full border rounded px-3 py-2" required>
                @foreach($vehicles as $v)
                    <option value="{{ $v->id }}" {{ old('vehicle_id', $repair->vehicle_id) == $v->id ? 'selected' : '' }}>{{ $v->plate_number }} - {{ $v->type }}</option>
                @endforeach
            </select>
        </div>

        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700">Tanggal Laporan <span class="text-red-500">*</span></label>
            <input type="date" name="report_date" value="{{ old('report_date', $repair->report_date->format('Y-m-d')) }}" class="w-full border rounded px-3 py-2" required>
        </div>

        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700">Keluhan <span class="text-red-500">*</span></label>
            <textarea name="complaint" rows="3" class="w-full border rounded px-3 py-2" required>{{ old('complaint', $repair->complaint) }}</textarea>
        </div>

        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700">Diagnosa Bengkel</label>
            <textarea name="diagnosis" rows="3" class="w-full border rounded px-3 py-2">{{ old('diagnosis', $repair->diagnosis) }}</textarea>
        </div>

        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700">Sparepart Diganti</label>
            <textarea name="parts_replaced" rows="2" class="w-full border rounded px-3 py-2">{{ old('parts_replaced', $repair->parts_replaced) }}</textarea>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700">Biaya Jasa</label>
                <input type="number" name="labor_cost" value="{{ old('labor_cost', $repair->labor_cost) }}" class="w-full border rounded px-3 py-2" min="0" step="0.01">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700">Biaya Sparepart</label>
                <input type="number" name="parts_cost" value="{{ old('parts_cost', $repair->parts_cost) }}" class="w-full border rounded px-3 py-2" min="0" step="0.01">
            </div>
        </div>

        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700">Status</label>
            <select name="status" class="w-full border rounded px-3 py-2">
                <option value="open" {{ old('status', $repair->status) == 'open' ? 'selected' : '' }}>Open</option>
                <option value="progress" {{ old('status', $repair->status) == 'progress' ? 'selected' : '' }}>Progress</option>
                <option value="done" {{ old('status', $repair->status) == 'done' ? 'selected' : '' }}>Selesai</option>
            </select>
        </div>

        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700">Catatan</label>
            <textarea name="notes" rows="2" class="w-full border rounded px-3 py-2">{{ old('notes', $repair->notes) }}</textarea>
        </div>

        <div class="flex justify-end space-x-2">
            <a href="{{ route('drms.repairs.index') }}" class="px-4 py-2 bg-gray-300 rounded hover:bg-gray-400">Batal</a>
            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Update</button>
        </div>
    </form>
</div>
@endsection