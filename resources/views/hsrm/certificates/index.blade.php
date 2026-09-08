@extends('layouts.hsrm-app')

@section('title', 'Certificates')
@section('page-title', 'Certificates Management')

@section('content')
<div class="flex flex-wrap justify-between items-center mb-4 gap-2">
    <div class="flex gap-2">
        @if(auth()->user()->hsrmAreas->isNotEmpty() || session('hsrm_role') === 'admin')
        <a href="{{ route('hsrm.certificates.create') }}" class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-blue-700">
            <i class="fas fa-plus mr-1"></i> Add Certificate
        </a>
        @endif
        <a href="{{ route('hsrm.certificates.export', request()->all()) }}" class="bg-green-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-green-700">
            <i class="fas fa-file-excel mr-1"></i> Export Excel
        </a>
    </div>

    <form method="GET" action="{{ route('hsrm.certificates.index') }}" class="flex flex-wrap gap-2 items-center w-full md:w-auto">
        <input type="text" name="search" placeholder="Search..." value="{{ request('search') }}" class="border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 flex-1 min-w-[120px]">
        <select name="status_verif" class="border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
            <option value="">All Status</option>
            <option value="pending" {{ request('status_verif') == 'pending' ? 'selected' : '' }}>Pending</option>
            <option value="verified" {{ request('status_verif') == 'verified' ? 'selected' : '' }}>Verified</option>
            <option value="revision" {{ request('status_verif') == 'revision' ? 'selected' : '' }}>Revision</option>
        </select>
        <div class="relative inline-block">
            <input type="text" id="area_name_filter" list="area-datalist-cert" autocomplete="off"
                   placeholder="Cari area..."
                   value="{{ optional($areas->firstWhere('id_area_kerja', request('area_id')))->nama_area }}"
                   class="border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 w-40">
            <input type="hidden" name="area_id" id="area_id_filter" value="{{ request('area_id') }}">
            <datalist id="area-datalist-cert">
                <option value="">All Areas</option>
                @foreach($areas as $area)
                    <option value="{{ $area->nama_area }}" data-id="{{ $area->id_area_kerja }}"></option>
                @endforeach
            </datalist>
        </div>

        {{-- FILTER TYPE --}}
        <select name="certificate_type_id" class="border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
            <option value="">All Types</option>
            @foreach($certificateTypes as $type)
                <option value="{{ $type->id }}" {{ request('certificate_type_id') == $type->id ? 'selected' : '' }}>
                    {{ $type->name }}
                </option>
            @endforeach
        </select>

        <input type="date" name="expired_from" value="{{ request('expired_from') }}" class="border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500" placeholder="Expired From">
        <input type="date" name="expired_to" value="{{ request('expired_to') }}" class="border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500" placeholder="Expired To">

        {{-- FILTER STATUS (Active/Warning/Expired) --}}
        <select name="status" class="border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
            <option value="">All Status (Expiry)</option>
            <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
            <option value="warning" {{ request('status') == 'warning' ? 'selected' : '' }}>Warning</option>
            <option value="expired" {{ request('status') == 'expired' ? 'selected' : '' }}>Expired</option>
        </select>

        {{-- FILTER RECOMMENDATION --}}
        <select name="rekomendasi" class="border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
            <option value="">All Recommendation</option>
            <option value="recommended" {{ request('rekomendasi') == 'recommended' ? 'selected' : '' }}>Recommended</option>
            <option value="not_recommended" {{ request('rekomendasi') == 'not_recommended' ? 'selected' : '' }}>Not Recommended</option>
            <option value="valid" {{ request('rekomendasi') == 'valid' ? 'selected' : '' }}>Valid</option>
            <option value="dash" {{ request('rekomendasi') == 'dash' ? 'selected' : '' }}>-</option>
        </select>

        <button type="submit" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded-lg text-sm">Filter</button>
        <a href="{{ route('hsrm.certificates.index') }}" class="text-gray-500 hover:text-gray-700 text-sm">Clear</a>
    </form>
</div>

{{-- TABEL VIEW (Desktop & Tablet) --}}
<div class="hidden md:block bg-white rounded-lg shadow-sm border overflow-x-auto">
    <table class="w-full text-sm">
        <thead>
            <tr class="bg-gray-50 text-left">
                <th class="p-3">Emp/Company</th>
                <th class="p-3">Certificate Number</th>
                <th class="p-3">Issuing Authority</th>
                <th class="p-3">Type</th>
                <th class="p-3">Area</th>
                <th class="p-3">Expired</th>
                <th class="p-3">Verification</th>
                <th class="p-3">Ownership</th>
                <th class="p-3">Recommendation</th>
                <th class="p-3">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($certificates as $cert)
            <tr class="border-t">
                <td class="p-3">{{ $cert->employee_name }}</td>
                <td class="p-3">{{ $cert->nik }}</td>
                <td class="p-3">{{ $cert->instansi_pengurusan ?? '-' }}</td>
                <td class="p-3">{{ $cert->certificateType->name ?? '-' }}</td>
                <td class="p-3">{{ $cert->area->nama_area ?? '-' }}</td>
                <td class="p-3">{{ $cert->expired_date ? $cert->expired_date->format('d M Y') : '-' }}</td>
                <td class="p-3">
                    <span class="status-badge 
                        @if($cert->status_verif == 'pending') status-pending
                        @elseif($cert->status_verif == 'verified') status-verified
                        @else status-revision @endif">
                        {{ ucfirst($cert->status_verif) }}
                    </span>
                </td>
                <td class="p-3 text-center">{{ $cert->status_kepemilikan ? '✔' : '✘' }}</td>
                <td class="p-3">
                    @if($cert->rekomendasi === 'recommended')
                        <span class="text-green-600">Recommended</span>
                    @elseif($cert->rekomendasi === 'not_recommended')
                        <span class="text-red-600">Not Recommended</span>
                    @elseif($cert->rekomendasi === 'valid')
                        <span class="text-blue-600">Valid</span>
                    @else
                        <span class="text-gray-400">-</span>
                    @endif
                </td>
                <td class="p-3 flex space-x-2">
                    <a href="{{ route('hsrm.certificates.show', $cert) }}" class="text-gray-600 hover:text-gray-800" title="Detail">
                        <i class="fas fa-eye"></i>
                    </a>
                    @if(auth()->user()->canEditInArea($cert->area_id) || session('hsrm_role') === 'admin')
                    <a href="{{ route('hsrm.certificates.edit', $cert) }}" class="text-blue-600 hover:text-blue-800">
                        <i class="fas fa-edit"></i>
                    </a>
                    @endif
                    @if(session('hsrm_role') === 'admin')
                    <form action="{{ route('hsrm.certificates.destroy', $cert) }}" method="POST" class="inline" onsubmit="return confirm('Delete this certificate? This action cannot be undone.');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-red-600 hover:text-red-800" title="Delete">
                            <i class="fas fa-trash"></i>
                        </button>
                    </form>
                    @endif
                </td>
            </tr>
            @empty
            <tr><td colspan="10" class="p-4 text-center text-gray-500">No certificates found.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

{{-- CARD VIEW (Mobile) --}}
<div class="md:hidden space-y-4">
    @forelse($certificates as $cert)
    <div class="bg-white rounded-xl soft-shadow border soft-border p-4">
        <div class="flex justify-between items-start mb-2">
            <div>
                <h4 class="font-semibold text-gray-800 text-lg">{{ $cert->employee_name }}</h4>
                <span class="text-sm text-gray-500">{{ $cert->certificateType->name ?? '-' }}</span>
            </div>
            <span class="status-badge 
                @if($cert->status_verif == 'pending') status-pending
                @elseif($cert->status_verif == 'verified') status-verified
                @else status-revision @endif">
                {{ ucfirst($cert->status_verif) }}
            </span>
        </div>

        <div class="grid grid-cols-2 gap-1 text-sm mt-2">
            <div class="col-span-2">
                <span class="text-gray-500">Certificate Number:</span>
                <span class="font-medium">{{ $cert->nik }}</span>
            </div>
            <div class="col-span-2">
                <span class="text-gray-500">Issuing Authority:</span>
                <span class="font-medium">{{ $cert->instansi_pengurusan ?? '-' }}</span>
            </div>
            <div>
                <span class="text-gray-500">Area:</span>
                <span class="font-medium">{{ $cert->area->nama_area ?? '-' }}</span>
            </div>
            <div>
                <span class="text-gray-500">Expired:</span>
                <span class="font-medium">{{ $cert->expired_date ? $cert->expired_date->format('d M Y') : '-' }}</span>
            </div>
            <div>
                <span class="text-gray-500">Ownership:</span>
                <span class="font-medium">{{ $cert->status_kepemilikan ? '✔' : '✘' }}</span>
            </div>
            <div class="col-span-2">
                <span class="text-gray-500">Recommendation:</span>
                <span class="font-medium">
                    @if($cert->rekomendasi === 'recommended')
                        <span class="text-green-600">Recommended</span>
                    @elseif($cert->rekomendasi === 'not_recommended')
                        <span class="text-red-600">Not Recommended</span>
                    @elseif($cert->rekomendasi === 'valid')
                        <span class="text-blue-600">Valid</span>
                    @else
                        <span class="text-gray-400">-</span>
                    @endif
                </span>
            </div>
        </div>

        <div class="flex justify-end gap-3 mt-3 pt-2 border-t soft-border">
            <a href="{{ route('hsrm.certificates.show', $cert) }}" class="text-gray-600 hover:text-gray-800 text-sm" title="Detail">
                <i class="fas fa-eye mr-1"></i> Detail
            </a>
            @if(auth()->user()->canEditInArea($cert->area_id) || session('hsrm_role') === 'admin')
            <a href="{{ route('hsrm.certificates.edit', $cert) }}" class="text-blue-600 hover:text-blue-800 text-sm" title="Edit">
                <i class="fas fa-edit mr-1"></i> Edit
            </a>
            @endif
            @if(session('hsrm_role') === 'admin')
            <form action="{{ route('hsrm.certificates.destroy', $cert) }}" method="POST" onsubmit="return confirm('Delete this certificate? This action cannot be undone.');">
                @csrf
                @method('DELETE')
                <button type="submit" class="text-red-600 hover:text-red-800 text-sm" title="Delete">
                    <i class="fas fa-trash mr-1"></i> Delete
                </button>
            </form>
            @endif
        </div>
    </div>
    @empty
    <div class="bg-white rounded-xl soft-shadow border soft-border p-6 text-center text-gray-500">
        No certificates found.
    </div>
    @endforelse
</div>

{{-- PAGINATION --}}
<div class="mt-6">
    {{ $certificates->appends(request()->query())->links() }}
</div>

@push('scripts')
<script>
    (function() {
        const nameInput = document.getElementById('area_name_filter');
        const idInput = document.getElementById('area_id_filter');
        const datalist = document.getElementById('area-datalist-cert');
        if (!nameInput || !idInput || !datalist) return;

        function syncAreaId() {
            const typed = nameInput.value.trim();
            if (typed === '') {
                idInput.value = '';
                return;
            }
            const match = Array.from(datalist.options).find(opt => opt.value === typed);
            idInput.value = match ? (match.dataset.id || '') : idInput.value;
        }

        nameInput.addEventListener('input', syncAreaId);
        nameInput.addEventListener('change', syncAreaId);
        nameInput.closest('form').addEventListener('submit', function() {
            // Kalau teks yang diketik tidak cocok persis dengan area manapun,
            // jangan kirim area_id yang salah/basi — anggap "All Areas".
            const typed = nameInput.value.trim();
            const match = Array.from(datalist.options).find(opt => opt.value === typed);
            if (typed !== '' && !match) {
                idInput.value = '';
            }
        });
    })();
</script>
@endpush
@endsection