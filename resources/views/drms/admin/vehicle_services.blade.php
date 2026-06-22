@extends('layouts.app_car_sidebar')

@section('content')
<div class="container mx-auto">
    <div class="flex justify-between items-center mb-4">
        <h1 class="text-2xl font-bold">Service Kendaraan</h1>
        <button onclick="document.getElementById('serviceModal').classList.remove('hidden')" class="bg-blue-600 text-white px-4 py-2 rounded">Tambah Service</button>
    </div>

    <div class="bg-white shadow rounded-lg overflow-hidden">
        <table class="min-w-full">
            <thead class="bg-gray-100">
                <tr>
                    <th class="px-4 py-2 text-left">Kendaraan</th>
                    <th class="px-4 py-2 text-left">Tanggal</th>
                    <th class="px-4 py-2 text-left">Odometer</th>
                    <th class="px-4 py-2 text-left">Biaya</th>
                    <th class="px-4 py-2 text-left">Deskripsi</th>
                    <th class="px-4 py-2 text-left">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($services as $service)
                <tr class="border-t">
                    <td class="px-4 py-2">{{ $service->vehicle->plate_number ?? '-' }}</td>
                    <td class="px-4 py-2">{{ $service->service_date }}</td>
                    <td class="px-4 py-2">{{ $service->odometer_at_service ?? '-' }}</td>
                    <td class="px-4 py-2">Rp {{ number_format($service->cost,0,',','.') }}</td>
                    <td class="px-4 py-2">{{ $service->description ?? '-' }}</td>
                    <td class="px-4 py-2">
                        @if($service->photo_evidence)
                            <a href="{{ route('drms.private.image', $service->photo_evidence) }}" target="_blank" class="text-blue-600">Foto</a>
                        @endif
                        <form action="{{ route('drms.admin.vehicle.services.delete', $service->id) }}" method="POST" class="inline" onsubmit="return confirm('Yakin?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-red-600 ml-2">Hapus</button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-4 py-6 text-center text-gray-500">Belum ada service.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
        {{ $services->links() }}
    </div>
</div>

<!-- Modal Tambah Service -->
<div id="serviceModal" class="hidden fixed inset-0 bg-black bg-opacity-30 flex items-center justify-center z-50">
    <div class="bg-white rounded-lg p-6 w-full max-w-md">
        <h2 class="text-lg font-bold mb-4">Tambah Service Kendaraan</h2>
        <form method="POST" action="{{ route('drms.admin.vehicle.services.store') }}" enctype="multipart/form-data">
            @csrf
            <div class="mb-3">
                <label class="block text-sm font-medium">Kendaraan</label>
                <select name="vehicle_id" class="w-full border rounded p-2" required>
                    <option value="">Pilih</option>
                    @foreach($vehicles as $v)
                        <option value="{{ $v->id }}">{{ $v->plate_number }} - {{ $v->type }}</option>
                    @endforeach
                </select>
            </div>
            <div class="mb-3">
                <label class="block text-sm font-medium">Tanggal Service</label>
                <input type="date" name="service_date" class="w-full border rounded p-2" required>
            </div>
            <div class="mb-3">
                <label class="block text-sm font-medium">Odometer (km)</label>
                <input type="number" name="odometer_at_service" class="w-full border rounded p-2">
            </div>
            <div class="mb-3">
                <label class="block text-sm font-medium">Biaya (Rp)</label>
                <input type="number" name="cost" step="0.01" class="w-full border rounded p-2" required>
            </div>
            <div class="mb-3">
                <label class="block text-sm font-medium">Deskripsi</label>
                <textarea name="description" rows="2" class="w-full border rounded p-2"></textarea>
            </div>
            <div class="mb-3">
                <label class="block text-sm font-medium">Foto Bukti</label>
                <input type="file" name="photo_evidence" accept="image/*" class="w-full border rounded p-2">
            </div>
            <div class="flex gap-2">
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded">Simpan</button>
                <button type="button" onclick="document.getElementById('serviceModal').classList.add('hidden')" class="px-4 py-2 bg-gray-300 rounded">Batal</button>
            </div>
        </form>
    </div>
</div>
@endsection