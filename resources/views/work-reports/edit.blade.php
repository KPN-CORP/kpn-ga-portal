@extends('layouts.app_work_sidebar')

@section('title', 'Edit Laporan Pekerjaan')
@section('breadcrumb', 'Edit Laporan')

@section('content')
<div class="max-w-2xl mx-auto bg-white p-6 rounded-lg shadow">
    <h2 class="text-xl font-semibold mb-4">Edit Laporan</h2>

    <form method="POST" action="{{ route('work-reports.update', $workReport) }}" enctype="multipart/form-data" id="reportForm">
        @csrf
        @method('PUT')

        <!-- Kategori -->
        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700">Kategori Pekerjaan</label>
            <select name="category_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
                @foreach($categories as $cat)
                    <option value="{{ $cat->id }}" {{ old('category_id', $workReport->category_id) == $cat->id ? 'selected' : '' }}>
                        {{ $cat->name }}
                    </option>
                @endforeach
            </select>
            @error('category_id')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>

        <!-- Foto Progres (dengan preview) -->
        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700">Foto Progres (opsional)</label>
            @if($workReport->photo_before)
                <div class="mb-2">
                    <img src="{{ route('private.storage', $workReport->photo_before) }}" class="h-32 object-cover rounded" alt="Foto Progres">
                    <p class="text-xs text-gray-500 mt-1">Foto Progres saat ini</p>
                </div>
            @endif
            <input type="file" name="photo_before" accept="image/*" class="mt-1 block w-full" id="photo_before">
            <p class="text-xs text-gray-500 mt-1">Bisa ambil langsung dari kamera HP. Maksimal 20 MB (akan dikompres).</p>
            @error('photo_before')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>

        <!-- Foto Selesai (dengan preview) -->
        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700">Foto Selesai (opsional)</label>
            @if($workReport->photo_after)
                <div class="mb-2">
                    <img src="{{ route('private.storage', $workReport->photo_after) }}" class="h-32 object-cover rounded" alt="Foto Selesai">
                    <p class="text-xs text-gray-500 mt-1">Foto Selesai saat ini</p>
                </div>
            @endif
            <input type="file" name="photo_after" accept="image/*" class="mt-1 block w-full" id="photo_after">
            <p class="text-xs text-gray-500 mt-1">Bisa ambil langsung dari kamera HP. Maksimal 20 MB (akan dikompres).</p>
            @error('photo_after')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>

        <!-- Lantai & Lokasi -->
        <div class="grid grid-cols-2 gap-4">
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700">Lantai</label>
                <input type="text" name="floor" value="{{ old('floor', $workReport->floor) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
                @error('floor')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700">Lokasi</label>
                <input type="text" name="location" value="{{ old('location', $workReport->location) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
                @error('location')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <!-- Tanggal -->
        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700">Tanggal Pekerjaan</label>
            <input type="date" name="report_date" value="{{ old('report_date', $workReport->report_date->format('Y-m-d')) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
            @error('report_date')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>

        <!-- Jam Mulai & Selesai -->
        <div class="grid grid-cols-2 gap-4">
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700">Jam Mulai</label>
                <input type="time" name="start_time" value="{{ old('start_time', \Carbon\Carbon::parse($workReport->start_time)->format('H:i')) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
                @error('start_time')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700">Jam Selesai</label>
                <input type="time" name="end_time" value="{{ old('end_time', \Carbon\Carbon::parse($workReport->end_time)->format('H:i')) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
                @error('end_time')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <!-- Keterangan -->
        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700">Keterangan Pekerjaan</label>
            <textarea name="description" rows="4" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>{{ old('description', $workReport->description) }}</textarea>
            @error('description')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div class="flex justify-end">
            <a href="{{ route('work-reports.index') }}" class="bg-gray-300 text-gray-800 px-4 py-2 rounded-md mr-2">Batal</a>
            <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-md">Update</button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('reportForm');
    if (!form) return;

    form.addEventListener('submit', function(e) {
        const beforeInput = document.getElementById('photo_before');
        const afterInput = document.getElementById('photo_after');
        const hasFiles = (beforeInput && beforeInput.files.length) || (afterInput && afterInput.files.length);

        if (!hasFiles) {
            return; // submit normal
        }

        e.preventDefault();
        handleSubmitWithCompression(form);
    });

    async function handleSubmitWithCompression(form) {
        const beforeInput = document.getElementById('photo_before');
        const afterInput = document.getElementById('photo_after');
        const filesToCompress = [];

        if (beforeInput && beforeInput.files.length) {
            filesToCompress.push({ input: beforeInput, name: 'photo_before' });
        }
        if (afterInput && afterInput.files.length) {
            filesToCompress.push({ input: afterInput, name: 'photo_after' });
        }

        const formData = new FormData(form);

        for (const { input, name } of filesToCompress) {
            const file = input.files[0];
            try {
                if (typeof imageCompression === 'function') {
                    const compressed = await imageCompression(file, {
                        maxSizeMB: 2,
                        maxWidthOrHeight: 1200,
                        useWebWorker: true,
                        fileType: 'image/jpeg',
                        initialQuality: 0.75,
                    });
                    formData.set(name, compressed, file.name);
                } else {
                    formData.set(name, file);
                }
            } catch (err) {
                console.warn('Kompresi gagal, menggunakan file asli', err);
                formData.set(name, file);
            }
        }

        const token = document.querySelector('meta[name="csrf-token"]')?.content;
        if (!token) {
            alert('CSRF token tidak ditemukan. Silakan refresh halaman.');
            return;
        }

        try {
            const response = await fetch(form.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': token,
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
            });

            if (response.redirected) {
                window.location.href = response.url;
                return;
            }

            if (response.ok) {
                const result = await response.json();
                if (result.redirect) {
                    window.location.href = result.redirect;
                } else {
                    window.location.href = '{{ route("work-reports.index") }}';
                }
            } else {
                const text = await response.text();
                alert('Error: ' + text);
            }
        } catch (err) {
            alert('Network error: ' + err.message);
        }
    }
});
</script>
@endpush