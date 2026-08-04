@extends('layouts.app_stock_sidebar')

@section('content')
<div class="space-y-6 text-sm text-gray-800 font-sans">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <h2 class="text-xl font-semibold text-gray-800">Area Kerja</h2>
        <a href="{{ route('stock-ctl.area.create') }}" 
           class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-semibold hover:bg-blue-700">
            + Tambah Area
        </a>
    </div>

    <div class="bg-white border rounded-xl overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-600">
                <tr>
                    <th class="px-4 py-3 text-left">Nama Area</th>
                    <th class="px-4 py-3 text-left">Bisnis Unit</th>
                    <th class="px-4 py-3 text-left">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse($areas as $area)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3">{{ $area->nama_area }}</td>
                    <td class="px-4 py-3">{{ $area->bisnisUnit->nama_bisnis_unit ?? '-' }}</td>
                    <td class="px-4 py-3">
                        <a href="{{ route('stock-ctl.area.edit', $area->id_area_kerja) }}" class="text-blue-600 hover:underline mr-2">Edit</a>
                        <form action="{{ route('stock-ctl.area.destroy', $area->id_area_kerja) }}" method="POST" class="inline" onsubmit="return confirm('Hapus area?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-red-600 hover:underline">Hapus</button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="3" class="py-10 text-center text-gray-500">Belum ada area kerja</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection