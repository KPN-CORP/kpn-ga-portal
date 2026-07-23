@extends('layouts.hsrm-app')

@section('title', 'Budget & Quota Control')
@section('page-title', 'Budget & Quota Management')

@push('styles')
    <style>
        .quota-readonly {
            background: #f9fafb;
            color: #1f2937;
            border: 1px solid #e5e7eb;
            border-radius: 0.375rem;
            padding: 0.25rem 0.75rem;
            display: inline-block;
            min-width: 60px;
            text-align: center;
        }
        .quota-input {
            width: 80px;
            border: 1px solid #d1d5db;
            border-radius: 0.375rem;
            padding: 0.25rem 0.5rem;
            text-align: center;
        }
        .budget-readonly {
            background: #f9fafb;
            color: #1f2937;
            border: 1px solid #e5e7eb;
            border-radius: 0.375rem;
            padding: 0.25rem 0.75rem;
            display: inline-block;
            min-width: 100px;
            text-align: right;
        }
        .budget-input {
            width: 120px;
            border: 1px solid #d1d5db;
            border-radius: 0.375rem;
            padding: 0.25rem 0.5rem;
            text-align: right;
        }
        .regulatory-readonly {
            background: #f9fafb;
            color: #1f2937;
            border: 1px solid #e5e7eb;
            border-radius: 0.375rem;
            padding: 0.25rem 0.75rem;
            display: inline-block;
            min-width: 50px;
            text-align: center;
        }
        .regulatory-input {
            width: 80px;
            border: 1px solid #d1d5db;
            border-radius: 0.375rem;
            padding: 0.25rem 0.5rem;
            text-align: center;
        }
        .action-btn {
            padding: 0.25rem 0.75rem;
            border-radius: 0.375rem;
            font-size: 0.75rem;
            font-weight: 500;
            transition: all 0.15s;
            cursor: pointer;
            border: none;
        }
        .action-btn:hover {
            opacity: 0.85;
        }
        .btn-edit {
            background: #e5e7eb;
            color: #1f2937;
        }
        .btn-edit:hover {
            background: #d1d5db;
        }
        .btn-save {
            background: #2563eb;
            color: white;
        }
        .btn-save:hover {
            background: #1d4ed8;
        }
        .btn-cancel {
            background: #9ca3af;
            color: white;
        }
        .btn-cancel:hover {
            background: #6b7280;
        }
        .area-error-text {
            color: #ef4444;
            font-size: 0.75rem;
            margin-top: 0.25rem;
            display: none;
        }
        .area-error-text.show {
            display: block;
        }
        .input-error {
            border-color: #ef4444 !important;
        }
        .dropdown-readonly {
            background: #f9fafb;
            color: #1f2937;
            border: 1px solid #e5e7eb;
            border-radius: 0.375rem;
            padding: 0.25rem 0.75rem;
            display: inline-block;
            min-width: 120px;
            text-align: center;
            font-size: 0.8rem;
        }
        .dropdown-input {
            min-width: 160px;
            border: 1px solid #d1d5db;
            border-radius: 0.375rem;
            padding: 0.25rem 0.5rem;
            font-size: 0.8rem;
        }
        .filter-select {
            border: 1px solid #d1d5db;
            border-radius: 0.375rem;
            padding: 0.4rem 0.75rem;
            font-size: 0.875rem;
            min-width: 160px;
        }
        .btn-download {
            color: white;
            padding: 0.4rem 1.25rem;
            border-radius: 0.375rem;
            font-weight: 500;
            transition: all 0.15s;
            border: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            text-decoration: none;
        }
        .btn-download:hover {
            opacity: 0.85;
            color: white;
        }
        .btn-download-green {
            background: #16a34a;
        }
        .btn-download-green:hover {
            background: #15803d;
        }
        .btn-download-blue {
            background: #2563eb;
        }
        .btn-download-blue:hover {
            background: #1d4ed8;
        }
        .toolbar {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 1rem;
            background: #f9fafb;
            padding: 0.75rem 1rem;
            border-radius: 0.75rem;
            border: 1px solid #e5e7eb;
            margin-bottom: 1.5rem;
        }
        .toolbar-label {
            font-weight: 500;
            color: #374151;
            font-size: 0.875rem;
            white-space: nowrap;
        }
        .area-input-group {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            flex-wrap: wrap;
        }
        .area-input-group input[type="text"] {
            border: 1px solid #d1d5db;
            border-radius: 0.375rem;
            padding: 0.4rem 0.75rem;
            font-size: 0.875rem;
            width: 220px;
        }
        .area-input-group .btn-show {
            background: #3b82f6;
            color: white;
            padding: 0.4rem 1rem;
            border-radius: 0.375rem;
            border: none;
            cursor: pointer;
            font-size: 0.875rem;
            transition: 0.15s;
        }
        .area-input-group .btn-show:hover {
            background: #2563eb;
        }
        .download-group {
            display: flex;
            gap: 0.5rem;
            margin-left: auto;
            flex-wrap: wrap;
        }
        @media (max-width: 768px) {
            .toolbar {
                flex-direction: column;
                align-items: stretch;
            }
            .download-group {
                margin-left: 0;
                justify-content: center;
            }
            .area-input-group input[type="text"] {
                width: 100%;
            }
        }
    </style>
@endpush

@section('content')
{{-- TOOLBAR: Filter + Area + Download --}}
<div class="toolbar">
    {{-- Filter Dropdown --}}
    <span class="toolbar-label">Filter:</span>
    <form method="GET" action="{{ route('hsrm.admin.quotas.index') }}" id="filter-form" class="flex items-center gap-3 flex-wrap">
        <input type="hidden" name="area_id" value="{{ $selectedArea ? $selectedArea->id_area_kerja : '' }}">
        <select name="filter" id="filter-select" class="filter-select" onchange="this.form.submit()">
            <option value="all_data_unit" {{ request('filter') == 'all_data_unit' ? 'selected' : '' }}>All Data Unit</option>
            <option value="all" {{ request('filter') == 'all' ? 'selected' : '' }}>All Data</option>
            <option value="certificate" {{ request('filter') == 'certificate' ? 'selected' : '' }}>All Certificate</option>
            <option value="equipment" {{ request('filter') == 'equipment' ? 'selected' : '' }}>All Equipment</option>
        </select>
    </form>

    {{-- Area Selector dengan Datalist --}}
    <div class="area-input-group">
        <form method="GET" action="{{ route('hsrm.admin.quotas.index') }}" id="area-select-form" class="flex items-center gap-2 flex-wrap">
            <input type="text"
                   id="area_name"
                   name="area_name"
                   list="area-list"
                   value="{{ $selectedArea ? $selectedArea->nama_area : old('area_name') }}"
                   placeholder="Cari area..."
                   autocomplete="off">
            <input type="hidden" name="area_id" id="area_id" value="{{ $selectedArea ? $selectedArea->id_area_kerja : old('area_id') }}">
            <input type="hidden" name="filter" value="{{ request('filter', 'all_data_unit') }}">
            <datalist id="area-list">
                @foreach($areas as $area)
                    <option value="{{ $area->nama_area }}" data-id="{{ $area->id_area_kerja }}">
                @endforeach
            </datalist>
            <button type="submit" class="btn-show">Show</button>
        </form>
        <div id="area-error" class="area-error-text">Area tidak valid. Pilih dari daftar.</div>
        @error('area_id') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
    </div>

    {{-- Tombol Download --}}
    <div class="download-group">
        @if($selectedArea)
            <a href="{{ route('hsrm.admin.quotas.export', ['mode' => 'single', 'area_id' => $selectedArea->id_area_kerja]) }}" 
               class="btn-download btn-download-green" target="_blank">
                <i class="fas fa-file-excel"></i> Download Area Ini
            </a>
        @endif
        <a href="{{ route('hsrm.admin.quotas.export', ['mode' => 'all']) }}" 
           class="btn-download btn-download-blue" target="_blank">
            <i class="fas fa-file-excel"></i> Download Semua Area
        </a>
    </div>
</div>

{{-- Konten Tabel --}}
@php
    $activeFilter = request('filter', 'all_data_unit');
    $showCertificate = in_array($activeFilter, ['all_data_unit', 'all', 'certificate']);
    $showEquipment = in_array($activeFilter, ['all_data_unit', 'all', 'equipment']);
@endphp
@if($selectedArea)
<div class="space-y-8">
    {{-- Certificate Quotas --}}
    @if($showCertificate)
    <div>
        <h3 class="text-lg font-semibold mb-3">📄 Certificates – {{ $selectedArea->nama_area }}</h3>
        <div class="bg-white rounded-xl shadow-sm border overflow-x-auto">
            <table class="w-full text-sm" id="certificate-table">
                <thead>
                    <tr class="bg-gray-50">
                        <th class="p-3 text-left">Certificate Type</th>
                        <th class="p-3 text-center">Regulatory</th>
                        <th class="p-3 text-center">Quota</th>
                        <th class="p-3 text-center">Active</th>
                        <th class="p-3 text-center">Expired</th>
                        <th class="p-3 text-center">Status</th>
                        <th class="p-3 text-center">Budget (Rp)</th>
                        <th class="p-3 text-center">Action</th>
                        <th class="p-3 text-center">Edit</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($certificateData as $row)
                    <tr data-id="{{ $row->type->id }}" data-module="certificate"
                        data-quota="{{ $row->quota }}" data-budget="{{ $row->budget ?? '' }}"
                        data-regulatory="{{ $row->regulatory ?? '' }}"
                        data-application-type="{{ $row->application_type ?? '' }}">
                        <td class="p-3">{{ $row->type->name }}</td>
                        <td class="p-3 text-center regulatory-cell">
                            <span class="regulatory-readonly">{{ $row->regulatory ?? '-' }}</span>
                            <input type="text" name="regulatory" value="{{ $row->regulatory ?? '' }}" class="regulatory-input hidden" maxlength="50">
                        </td>
                        <td class="p-3 text-center quota-cell">
                            <span class="quota-readonly">{{ $row->quota }}</span>
                            <input type="number" name="quota" value="{{ $row->quota }}" min="0" class="quota-input hidden">
                        </td>
                        <td class="p-3 text-center font-medium">
                            <a href="{{ route('hsrm.certificates.index', ['area_id' => $selectedArea->id_area_kerja, 'certificate_type_id' => $row->type->id, 'status_verif' => 'verified']) }}" 
                               class="text-blue-600 hover:underline" target="_blank">
                                {{ $row->active }}
                            </a>
                        </td>
                        <td class="p-3 text-center {{ $row->expired > 0 ? 'text-red-600 font-semibold' : 'text-gray-500' }}">
                            <a href="{{ route('hsrm.certificates.index', ['area_id' => $selectedArea->id_area_kerja, 'certificate_type_id' => $row->type->id, 'expired_to' => date('Y-m-d')]) }}" 
                               class="hover:underline" target="_blank">
                                {{ $row->expired }}
                            </a>
                        </td>
                        <td class="p-3 text-center">
                            @php
                                $diff = $row->active - $row->quota;
                                $status = '';
                                $color = '';
                                if ($diff < 0) {
                                    $status = 'Short '.abs($diff);
                                    $color = 'text-yellow-600';
                                } elseif ($diff == 0) {
                                    $status = 'Sufficient';
                                    $color = 'text-green-600';
                                } else {
                                    $status = 'Over '.$diff;
                                    $color = 'text-red-600';
                                }
                                if ($row->expired > 0) {
                                    $status .= ' (Expired: '.$row->expired.')';
                                    $color = 'text-orange-600';
                                }
                            @endphp
                            <span class="font-medium {{ $color }}">{{ $status }}</span>
                        </td>
                        <td class="p-3 text-center budget-cell">
                            <span class="budget-readonly">{{ number_format($row->budget ?? 0, 0, ',', '.') }}</span>
                            <input type="number" name="budget" value="{{ $row->budget ?? '' }}" step="0.01" min="0" class="budget-input hidden">
                        </td>
                        <td class="p-3 text-center action-dropdown-cell">
                            <span class="dropdown-readonly">{{ $row->application_type ?? '-' }}</span>
                            <select name="application_type" class="dropdown-input hidden">
                                <option value="">-- Select --</option>
                                <option value="Extension Application" {{ $row->application_type == 'Extension Application' ? 'selected' : '' }}>Extension Application</option>
                                <option value="Certification Application" {{ $row->application_type == 'Certification Application' ? 'selected' : '' }}>Certification Application</option>
                                <option value="License Application" {{ $row->application_type == 'License Application' ? 'selected' : '' }}>License Application</option>
                                <option value="Amendment & Extension Application" {{ $row->application_type == 'Amendment & Extension Application' ? 'selected' : '' }}>Amendment & Extension Application</option>
                            </select>
                        </td>
                        <td class="p-3 text-center action-cell">
                            <button type="button" class="action-btn btn-edit">Edit</button>
                            <button type="button" class="action-btn btn-save hidden">Save</button>
                            <button type="button" class="action-btn btn-cancel hidden">Cancel</button>
                            <form action="{{ route('hsrm.admin.quotas.update') }}" method="POST" class="inline-block hidden save-form">
                                @csrf
                                <input type="hidden" name="area_id" value="{{ $selectedArea->id_area_kerja }}">
                                <input type="hidden" name="module" value="certificate">
                                <input type="hidden" name="type_id" value="{{ $row->type->id }}">
                                <input type="hidden" name="quota" value="{{ $row->quota }}">
                                <input type="hidden" name="budget" value="{{ $row->budget ?? '' }}">
                                <input type="hidden" name="regulatory" value="{{ $row->regulatory ?? '' }}">
                                <input type="hidden" name="application_type" value="{{ $row->application_type ?? '' }}">
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    {{-- Equipment Quotas --}}
    @if($showEquipment)
    <div>
        <h3 class="text-lg font-semibold mb-3">🔧 Equipments – {{ $selectedArea->nama_area }}</h3>
        <div class="bg-white rounded-xl shadow-sm border overflow-x-auto">
            <table class="w-full text-sm" id="equipment-table">
                <thead>
                    <tr class="bg-gray-50">
                        <th class="p-3 text-left">Equipment Type</th>
                        <th class="p-3 text-center">Quota (items)</th>
                        <th class="p-3 text-center">Active (items)</th>
                        <th class="p-3 text-center">Expired (items)</th>
                        <th class="p-3 text-center">Status</th>
                        <th class="p-3 text-center">Budget (Rp)</th>
                        <th class="p-3 text-center">Action</th>
                        <th class="p-3 text-center">Edit</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($equipmentData as $row)
                    <tr data-id="{{ $row->type->id }}" data-module="equipment"
                        data-quota="{{ $row->quota }}" data-budget="{{ $row->budget ?? '' }}"
                        data-application-type="{{ $row->application_type ?? '' }}">
                        <td class="p-3">{{ $row->type->name }}</td>
                        <td class="p-3 text-center quota-cell">
                            <span class="quota-readonly">{{ $row->quota }}</span>
                            <input type="number" name="quota" value="{{ $row->quota }}" min="0" class="quota-input hidden">
                        </td>
                        <td class="p-3 text-center font-medium">
                            <a href="{{ route('hsrm.equipments.index', ['area_id' => $selectedArea->id_area_kerja, 'equipment_type_id' => $row->type->id, 'status_verif' => 'verified']) }}" 
                               class="text-blue-600 hover:underline" target="_blank">
                                {{ $row->active }}
                            </a>
                        </td>
                        <td class="p-3 text-center {{ $row->expired > 0 ? 'text-red-600 font-semibold' : 'text-gray-500' }}">
                            <a href="{{ route('hsrm.equipments.index', ['area_id' => $selectedArea->id_area_kerja, 'equipment_type_id' => $row->type->id, 'expired_to' => date('Y-m-d')]) }}" 
                               class="hover:underline" target="_blank">
                                {{ $row->expired }}
                            </a>
                        </td>
                        <td class="p-3 text-center">
                            @php
                                $diff = $row->active - $row->quota;
                                $status = '';
                                $color = '';
                                if ($diff < 0) {
                                    $status = 'Short '.abs($diff);
                                    $color = 'text-yellow-600';
                                } elseif ($diff == 0) {
                                    $status = 'Sufficient';
                                    $color = 'text-green-600';
                                } else {
                                    $status = 'Over '.$diff;
                                    $color = 'text-red-600';
                                }
                                if ($row->expired > 0) {
                                    $status .= ' (Expired: '.$row->expired.')';
                                    $color = 'text-orange-600';
                                }
                            @endphp
                            <span class="font-medium {{ $color }}">{{ $status }}</span>
                        </td>
                        <td class="p-3 text-center budget-cell">
                            <span class="budget-readonly">{{ number_format($row->budget ?? 0, 0, ',', '.') }}</span>
                            <input type="number" name="budget" value="{{ $row->budget ?? '' }}" step="0.01" min="0" class="budget-input hidden">
                        </td>
                        <td class="p-3 text-center action-dropdown-cell">
                            <span class="dropdown-readonly">{{ $row->application_type ?? '-' }}</span>
                            <select name="application_type" class="dropdown-input hidden">
                                <option value="">-- Select --</option>
                                <option value="Extension Application" {{ $row->application_type == 'Extension Application' ? 'selected' : '' }}>Extension Application</option>
                                <option value="Certification Application" {{ $row->application_type == 'Certification Application' ? 'selected' : '' }}>Certification Application</option>
                                <option value="License Application" {{ $row->application_type == 'License Application' ? 'selected' : '' }}>License Application</option>
                                <option value="Amendment & Extension Application" {{ $row->application_type == 'Amendment & Extension Application' ? 'selected' : '' }}>Amendment & Extension Application</option>
                            </select>
                        </td>
                        <td class="p-3 text-center action-cell">
                            <button type="button" class="action-btn btn-edit">Edit</button>
                            <button type="button" class="action-btn btn-save hidden">Save</button>
                            <button type="button" class="action-btn btn-cancel hidden">Cancel</button>
                            <form action="{{ route('hsrm.admin.quotas.update') }}" method="POST" class="inline-block hidden save-form">
                                @csrf
                                <input type="hidden" name="area_id" value="{{ $selectedArea->id_area_kerja }}">
                                <input type="hidden" name="module" value="equipment">
                                <input type="hidden" name="type_id" value="{{ $row->type->id }}">
                                <input type="hidden" name="quota" value="{{ $row->quota }}">
                                <input type="hidden" name="budget" value="{{ $row->budget ?? '' }}">
                                <input type="hidden" name="application_type" value="{{ $row->application_type ?? '' }}">
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif
</div>
@else
    <div class="bg-gray-50 p-8 text-center text-gray-500 rounded-xl border">
        Pilih area di atas untuk melihat dan mengelola kuota & anggaran.
    </div>
@endif
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // ============================================================
        // AREA VALIDATION (DATALIST)
        // ============================================================
        const areaInput = document.getElementById('area_name');
        const areaIdHidden = document.getElementById('area_id');
        const datalist = document.getElementById('area-list');
        const errorDiv = document.getElementById('area-error');
        const form = document.getElementById('area-select-form');

        function validateArea() {
            const typedValue = areaInput.value.trim();
            const options = datalist.options;
            let found = false;
            let foundId = null;

            for (let opt of options) {
                if (opt.value === typedValue) {
                    found = true;
                    foundId = opt.dataset.id;
                    break;
                }
            }

            if (found && foundId) {
                areaIdHidden.value = foundId;
                areaInput.classList.remove('input-error');
                errorDiv.classList.remove('show');
                return true;
            } else {
                if (typedValue === '') {
                    areaIdHidden.value = '';
                    areaInput.classList.remove('input-error');
                    errorDiv.classList.remove('show');
                    return true;
                }
                areaIdHidden.value = '';
                areaInput.classList.add('input-error');
                errorDiv.classList.add('show');
                return false;
            }
        }

        areaInput.addEventListener('input', validateArea);
        areaInput.addEventListener('blur', validateArea);

        form.addEventListener('submit', function(e) {
            if (!validateArea()) {
                e.preventDefault();
                areaInput.focus();
                alert('Silakan pilih area dari daftar yang tersedia.');
            }
        });

        // ============================================================
        // EDIT / SAVE / CANCEL per row
        // ============================================================
        function setupRow(row) {
            const $row = row;
            const $btnEdit = $row.querySelector('.btn-edit');
            const $btnSave = $row.querySelector('.btn-save');
            const $btnCancel = $row.querySelector('.btn-cancel');
            const $form = $row.querySelector('.save-form');

            const $quotaReadonly = $row.querySelector('.quota-readonly');
            const $quotaInput = $row.querySelector('.quota-input');
            const $budgetReadonly = $row.querySelector('.budget-readonly');
            const $budgetInput = $row.querySelector('.budget-input');
            const $regulatoryReadonly = $row.querySelector('.regulatory-readonly');
            const $regulatoryInput = $row.querySelector('.regulatory-input');
            const $dropdownReadonly = $row.querySelector('.dropdown-readonly');
            const $dropdownInput = $row.querySelector('select[name="application_type"]');

            let initialQuota = $quotaReadonly.textContent.trim();
            let initialBudget = $budgetReadonly.textContent.trim().replace(/\./g, '');
            let initialRegulatory = $regulatoryReadonly ? $regulatoryReadonly.textContent.trim() : '';
            let initialAppType = $dropdownReadonly ? $dropdownReadonly.textContent.trim() : '';

            $btnEdit.addEventListener('click', function() {
                $quotaReadonly.classList.add('hidden');
                $quotaInput.classList.remove('hidden');
                $budgetReadonly.classList.add('hidden');
                $budgetInput.classList.remove('hidden');
                if ($regulatoryReadonly) {
                    $regulatoryReadonly.classList.add('hidden');
                    $regulatoryInput.classList.remove('hidden');
                }
                if ($dropdownReadonly) {
                    $dropdownReadonly.classList.add('hidden');
                    $dropdownInput.classList.remove('hidden');
                }

                $quotaInput.value = initialQuota;
                $budgetInput.value = initialBudget;
                if ($regulatoryInput) $regulatoryInput.value = initialRegulatory;
                if ($dropdownInput) $dropdownInput.value = initialAppType;

                $btnEdit.classList.add('hidden');
                $btnSave.classList.remove('hidden');
                $btnCancel.classList.remove('hidden');
            });

            $btnCancel.addEventListener('click', function() {
                $quotaReadonly.classList.remove('hidden');
                $quotaInput.classList.add('hidden');
                $budgetReadonly.classList.remove('hidden');
                $budgetInput.classList.add('hidden');
                if ($regulatoryReadonly) {
                    $regulatoryReadonly.classList.remove('hidden');
                    $regulatoryInput.classList.add('hidden');
                }
                if ($dropdownReadonly) {
                    $dropdownReadonly.classList.remove('hidden');
                    $dropdownInput.classList.add('hidden');
                }

                $quotaReadonly.textContent = initialQuota;
                $budgetReadonly.textContent = formatNumber(parseFloat(initialBudget) || 0);
                if ($regulatoryReadonly) $regulatoryReadonly.textContent = initialRegulatory;
                if ($dropdownReadonly) $dropdownReadonly.textContent = initialAppType;

                $btnEdit.classList.remove('hidden');
                $btnSave.classList.add('hidden');
                $btnCancel.classList.add('hidden');
            });

            $btnSave.addEventListener('click', function() {
                const newQuota = $quotaInput.value;
                const newBudget = $budgetInput.value;
                const newRegulatory = $regulatoryInput ? $regulatoryInput.value : '';
                const newAppType = $dropdownInput ? $dropdownInput.value : '';

                $form.querySelector('input[name="quota"]').value = newQuota;
                $form.querySelector('input[name="budget"]').value = newBudget;
                if ($form.querySelector('input[name="regulatory"]')) {
                    $form.querySelector('input[name="regulatory"]').value = newRegulatory;
                }
                if ($form.querySelector('input[name="application_type"]')) {
                    $form.querySelector('input[name="application_type"]').value = newAppType;
                }

                $form.submit();
            });
        }

        document.querySelectorAll('#certificate-table tbody tr, #equipment-table tbody tr').forEach(function(row) {
            setupRow(row);
        });

        function formatNumber(num) {
            if (isNaN(num)) return '0';
            return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        }
    });
</script>
@endpush