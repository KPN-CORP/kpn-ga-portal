@extends('layouts.app-sidebar')

@section('title', $project->title . ' - Detail Project')

@section('content')
<div class="w-full px-4 sm:px-6 lg:px-8 py-6">
    {{-- Card Info Project --}}
    <div class="bg-white rounded-2xl shadow-md border border-gray-100 overflow-hidden mb-6">
        <div class="bg-gradient-to-r from-indigo-600 to-indigo-700 px-6 py-4">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-xl font-semibold text-white flex items-center gap-2">
                        <i class="fas fa-chart-line"></i> {{ $project->title }}
                    </h1>
                    <p class="text-indigo-100 text-sm mt-1">
                        <i class="far fa-calendar-alt mr-1"></i>
                        Tahun: {{ $project->start_date->format('Y') }}
                    </p>
                </div>
                <div class="flex gap-2">
                    <a href="{{ route('task-m.index') }}" class="bg-white/20 hover:bg-white/30 text-white rounded-full p-2 transition" title="Kembali">
                        <i class="fas fa-arrow-left text-lg"></i>
                    </a>
                    <button onclick="openUnitModal()" class="bg-white/20 hover:bg-white/30 text-white rounded-full p-2 transition">
                        <i class="fas fa-plus text-lg"></i>
                    </button>
                </div>
            </div>
        </div>

        {{-- Progress Bar --}}
        <div class="p-6">
            <div class="flex justify-between items-center mb-2">
                <span class="text-sm font-medium text-gray-600">Progress Penyelesaian</span>
                <span class="text-2xl font-bold text-indigo-600">{{ $project->progressPercentage() }}%</span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-3 overflow-hidden">
                <div class="bg-indigo-600 h-3 rounded-full transition-all duration-300" style="width: {{ $project->progressPercentage() }}%"></div>
            </div>
            <div class="flex justify-between text-xs text-gray-500 mt-2">
                <span>✅ Selesai: {{ $project->doneUnitsCount() }}</span>
                <span>📦 Unit Aktif: {{ $project->activeUnitsCount() }}</span>
                <span>🚫 Dibatalkan: {{ $project->units->where('status', 'cancelled')->count() }}</span>
            </div>
            <div class="mt-3 text-xs text-gray-400 bg-gray-100 p-2 rounded-lg">
                <i class="fas fa-info-circle text-indigo-400 mr-1"></i> 
                Catatan: Status "Batal" tidak mempengaruhi progres. Hanya unit "Selesai" yang menambah persentase.
            </div>
        </div>
    </div>

    {{-- Daftar Progres --}}
    <div class="bg-white rounded-2xl shadow-md border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 bg-gray-50 border-b border-gray-100 flex justify-between items-center">
            <h2 class="font-semibold text-gray-800"><i class="fas fa-list-check mr-2 text-indigo-600"></i> Daftar Progres</h2>
        </div>

        @if(session('success'))
            <div class="m-4 p-3 bg-green-100 text-green-700 rounded-xl text-sm">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="m-4 p-3 bg-red-100 text-red-700 rounded-xl text-sm">{{ session('error') }}</div>
        @endif

        @if($project->units->count())
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">No</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Deskripsi</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Dibuat</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Selesai / Dibatalkan</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($project->units as $index => $unit)
                            @php
                                $isFinal = $unit->isFinal();
                                $statusText = $unit->status == 'done' ? 'Selesai' : ($unit->status == 'cancelled' ? 'Dibatalkan' : 'Pending');
                                $statusClass = $unit->status == 'done' ? 'bg-green-100 text-green-700' : ($unit->status == 'cancelled' ? 'bg-red-100 text-red-700' : 'bg-yellow-100 text-yellow-700');
                                $createdDate = $unit->created_at ? $unit->created_at->format('d/m/Y H:i') : '-';
                                $finalDate = ($unit->status == 'done' || $unit->status == 'cancelled') 
                                    ? ($unit->updated_at ? $unit->updated_at->format('d/m/Y H:i') : '-')
                                    : '-';
                            @endphp
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $loop->iteration }}</td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-2">
                                        <span class="text-gray-800 font-medium">{{ $unit->description }}</span>
                                    </div>
                                    <form id="edit-form-{{ $unit->id }}" action="{{ route('task-m.update-unit-description', [$project->id, $unit->id]) }}" method="POST" class="hidden mt-2">
                                        @csrf @method('PUT')
                                        <div class="flex gap-2">
                                            <input type="text" name="description" value="{{ $unit->description }}" class="flex-1 rounded-lg border-gray-200 text-sm">
                                            <button type="submit" class="bg-indigo-500 text-white px-3 py-1 rounded-lg text-sm">Simpan</button>
                                            <button type="button" onclick="toggleEditForm({{ $unit->id }})" class="text-gray-500 text-sm">Batal</button>
                                        </div>
                                    </form>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-block text-xs px-2 py-1 rounded-full {{ $statusClass }}">
                                        {{ $statusText }}
                                    </span>
                                    @if($isFinal)
                                        <span class="ml-2 text-xs text-gray-400"><i class="fas fa-lock"></i></span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $createdDate }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $finalDate }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-right">
                                    <div class="flex justify-end gap-2">
                                        @if(!$isFinal)
                                            <form action="{{ route('task-m.update-unit-status', [$project->id, $unit->id]) }}" method="POST" class="inline">
                                                @csrf @method('PATCH')
                                                <input type="hidden" name="action" value="done">
                                                <button type="submit" class="bg-green-500 hover:bg-green-600 text-white rounded-full w-8 h-8 flex items-center justify-center" title="Selesai">
                                                    <i class="fas fa-check text-sm"></i>
                                                </button>
                                            </form>
                                            <form action="{{ route('task-m.update-unit-status', [$project->id, $unit->id]) }}" method="POST" class="inline">
                                                @csrf @method('PATCH')
                                                <input type="hidden" name="action" value="cancel">
                                                <button type="submit" class="bg-red-500 hover:bg-red-600 text-white rounded-full w-8 h-8 flex items-center justify-center" title="Batal">
                                                    <i class="fas fa-times text-sm"></i>
                                                </button>
                                            </form>
                                        @else
                                            <span class="text-xs text-gray-400 bg-gray-100 px-2 py-1 rounded-full">
                                                <i class="fas fa-check-circle"></i> Final
                                            </span>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="p-10 text-center text-gray-400">
                <i class="fas fa-clipboard-list text-4xl mb-3"></i>
                <p>Belum ada progres. Klik tombol + di kanan atas untuk menambah.</p>
            </div>
        @endif
    </div>
</div>

<div id="unitModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50" onclick="if(event.target === this) closeUnitModal()">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-md p-6 mx-4">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-xl font-bold text-gray-800">Tambah Progres Baru</h3>
            <button onclick="closeUnitModal()" class="text-gray-400 hover:text-gray-600">&times;</button>
        </div>
        <form action="{{ route('task-m.add-unit', $project->id) }}" method="POST">
            @csrf
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Deskripsi Progres</label>
                <textarea name="description" rows="3" required class="w-full rounded-xl border-gray-200 focus:border-indigo-400 focus:ring focus:ring-indigo-200" placeholder="Contoh: Riset, Desain, Testing, ..."></textarea>
            </div>
            <div class="flex justify-end gap-2">
                <button type="button" onclick="closeUnitModal()" class="px-4 py-2 border rounded-xl">Batal</button>
                <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-xl hover:bg-indigo-700">Tambah</button>
            </div>
        </form>
    </div>
</div>

<script>
    function toggleEditForm(unitId) {
        const form = document.getElementById(`edit-form-${unitId}`);
        form.classList.toggle('hidden');
    }
    function openUnitModal() {
        document.getElementById('unitModal').classList.remove('hidden');
        document.getElementById('unitModal').classList.add('flex');
    }
    function closeUnitModal() {
        document.getElementById('unitModal').classList.add('hidden');
        document.getElementById('unitModal').classList.remove('flex');
    }
</script>
@endsection