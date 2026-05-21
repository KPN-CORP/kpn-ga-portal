@extends('layouts.app_car_sidebar')

@php
    use Carbon\Carbon;
@endphp

@section('content')
<div class="max-w-3xl mx-auto bg-white p-6 rounded-lg shadow">
    <h1 class="text-2xl font-bold mb-6">Proses Request #{{ $driverRequest->request_no }}</h1>

    @if($errors->any())
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            <ul class="list-disc pl-5">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="mb-6 p-4 bg-gray-50 rounded">
        <h2 class="font-semibold mb-2">Detail Permintaan</h2>
        <dl class="grid grid-cols-2 gap-2 text-sm">
            <dt>Pemohon:</dt>
            <dd>
                {{ $driverRequest->requester->name }}
                @if($driverRequest->created_at)
                    <span class="text-gray-500 text-xs ml-1">
                        ({{ Carbon::parse($driverRequest->created_at)->timezone('Asia/Jakarta')->format('d M Y H:i') }})
                    </span>
                @endif
            </dd>
            <dt>Unit/Area:</dt>
            <dd>{{ $driverRequest->requester->drmsProfile->unit ?? '-' }} / {{ $driverRequest->requester->drmsProfile->area ?? '-' }}</dd>
            <dt>Tipe Perjalanan:</dt>
            <dd>
                @if($driverRequest->trip_type === 'round_trip')
                    <span class="px-2 py-1 rounded-full text-xs bg-purple-100 text-purple-800">Pulang Pergi</span>
                @else
                    <span class="px-2 py-1 rounded-full text-xs bg-gray-100 text-gray-800">Sekali Jalan</span>
                @endif
            </dd>
            <dt>Tanggal:</dt><dd>{{ Carbon::parse($driverRequest->usage_date)->format('d M Y') }}</dd>
            <dt>Jam Berangkat:</dt><dd>{{ Carbon::parse($driverRequest->start_time)->format('H:i') }}</dd>
            <dt>Jam Selesai:</dt><dd>{{ $driverRequest->end_time ? Carbon::parse($driverRequest->end_time)->format('H:i') : 'Belum ditentukan' }}</dd>
            @if($driverRequest->trip_type === 'round_trip')
            <dt>Tanggal Kembali:</dt><dd>{{ Carbon::parse($driverRequest->return_date)->format('d M Y') }} ({{ Carbon::parse($driverRequest->return_time)->format('H:i') }})</dd>
            @endif
            <dt>Penjemputan:</dt><dd>{{ $driverRequest->pickup_location }}</dd>
            <dt>Tujuan:</dt><dd>{{ $driverRequest->destination }}</dd>
            <dt>Keperluan:</dt><dd>{{ $driverRequest->purpose ?? '-' }}</dd>
            <dt>Status:</dt>
            <dd>
                @php
                    $statusLabels = [
                        'pending_l1' => 'Menunggu Approval Atasan',
                        'approved_l1' => 'Disetujui Atasan',
                        'rejected_l1' => 'Ditolak Atasan',
                        'approved_admin' => 'Disetujui GA',
                        'rejected_admin' => 'Ditolak GA',
                        'completed' => 'Selesai',
                    ];
                    $statusColors = [
                        'pending_l1' => 'bg-yellow-100 text-yellow-800',
                        'approved_l1' => 'bg-blue-100 text-blue-800',
                        'rejected_l1' => 'bg-red-100 text-red-800',
                        'approved_admin' => 'bg-green-100 text-green-800',
                        'rejected_admin' => 'bg-red-100 text-red-800',
                        'completed' => 'bg-gray-100 text-gray-800',
                    ];
                @endphp
                <span class="px-2 py-1 rounded-full text-xs font-semibold {{ $statusColors[$driverRequest->status] ?? 'bg-gray-100 text-gray-800' }}">
                    {{ $statusLabels[$driverRequest->status] ?? ucfirst($driverRequest->status) }}
                </span>
            </dd>
            @if($driverRequest->approverL1)
            <dt>Disetujui Oleh (L1):</dt>
            <dd>{{ $driverRequest->approverL1->name }} @if($driverRequest->approved_l1_at) ({{ Carbon::parse($driverRequest->approved_l1_at)->format('d M Y H:i') }}) @endif</dd>
            @endif
            @if($driverRequest->rejection_reason)
            <dt>Alasan:</dt>
            <dd class="text-red-600">{{ $driverRequest->rejection_reason }}</dd>
            @endif
        </dl>
    </div>

    <form method="POST" action="{{ route('drms.approval.admin.update', $driverRequest->id) }}" id="processForm">
        @csrf
        @method('PUT')

        <div class="mb-4">
            <label class="block text-sm font-medium mb-2">Jenis Transportasi</label>
            <div class="space-y-2">
                <label class="flex items-center">
                    <input type="radio" name="transport_type" value="company_driver" class="mr-2" {{ old('transport_type', $driverRequest->transport_type) == 'company_driver' ? 'checked' : '' }} required>
                    Driver & Mobil Perusahaan
                </label>
                <label class="flex items-center">
                    <input type="radio" name="transport_type" value="voucher" class="mr-2" {{ old('transport_type', $driverRequest->transport_type) == 'voucher' ? 'checked' : '' }}>
                    Voucher (Grab/Gojek/Taxi)
                </label>
                <label class="flex items-center">
                    <input type="radio" name="transport_type" value="rental" class="mr-2" {{ old('transport_type', $driverRequest->transport_type) == 'rental' ? 'checked' : '' }}>
                    Mobil Rental
                </label>
            </div>
        </div>

        <div id="company_fields" class="{{ old('transport_type', $driverRequest->transport_type) == 'company_driver' ? '' : 'hidden' }} mb-4">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium mb-1">Pilih Driver</label>
                    <select name="driver_id" class="w-full border rounded p-2" id="driver_select">
                        <option value="">-- Pilih Driver --</option>
                        @foreach($drivers as $driver)
                            <option value="{{ $driver->id }}" {{ old('driver_id', $driverRequest->driver_id) == $driver->id ? 'selected' : '' }}>
                                {{ $driver->name }} ({{ $driver->phone }}) - {{ ucfirst($driver->status) }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Pilih Kendaraan</label>
                    <select name="vehicle_id" class="w-full border rounded p-2" id="vehicle_select">
                        <option value="">-- Pilih Kendaraan --</option>
                        @foreach($vehicles as $vehicle)
                            <option value="{{ $vehicle->id }}" {{ old('vehicle_id', $driverRequest->vehicle_id) == $vehicle->id ? 'selected' : '' }}>
                                {{ $vehicle->type }} - {{ $vehicle->plate_number }} ({{ $vehicle->capacity }} kursi)
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        <div id="voucher_fields" class="{{ old('transport_type', $driverRequest->transport_type) == 'voucher' ? '' : 'hidden' }} mb-4">
            <label class="block text-sm font-medium mb-1">Pilih Voucher</label>
            <select name="voucher_id" class="w-full border rounded p-2" id="voucher_select">
                <option value="">-- Pilih Voucher --</option>
                @foreach($vouchers as $voucher)
                    <option value="{{ $voucher->id }}" {{ old('voucher_id', $driverRequest->voucher_id) == $voucher->id ? 'selected' : '' }}>
                        {{ $voucher->code }} - {{ ucfirst($voucher->type) }} (Rp {{ number_format($voucher->nominal,0,',','.') }})
                    </option>
                @endforeach
            </select>
        </div>

        <div id="rental_fields" class="{{ old('transport_type', $driverRequest->transport_type) == 'rental' ? '' : 'hidden' }} mb-4">
            <p class="text-gray-600">Untuk rental, akan diproses lebih lanjut oleh tim GA.</p>
        </div>

        <div class="mb-4">
            <label class="block text-sm font-medium mb-1">Keterangan (opsional)</label>
            <textarea name="keterangan" rows="2" class="w-full border rounded p-2" placeholder="Catatan tambahan...">{{ old('keterangan') }}</textarea>
        </div>

        <div class="flex justify-end space-x-2">
            <a href="{{ route('drms.approval.admin.index') }}" class="px-4 py-2 bg-gray-300 rounded">Batal</a>
            <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Proses</button>
        </div>
    </form>

    {{-- Tombol Forward ke BU Lain --}}
    <div class="mt-6 pt-4 border-t">
        <button type="button" onclick="openForwardModal()"
                class="px-4 py-2 bg-yellow-600 text-white rounded hover:bg-yellow-700">
            ➡️ Forward ke Business Unit Lain
        </button>
    </div>
</div>

{{-- Modal Forward --}}
<div id="forwardModal" class="fixed inset-0 bg-black bg-opacity-30 hidden items-center justify-center z-50" style="display: none;">
    <div class="bg-white rounded-lg p-6 w-full max-w-md">
        <h3 class="text-lg font-semibold mb-4">Alihkan ke Business Unit Lain</h3>
        <form method="POST" action="{{ route('drms.approval.admin.forward', $driverRequest->id) }}">
            @csrf
            @method('PUT')
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Pilih Business Unit Tujuan</label>
                <select name="target_business_unit_id" class="w-full border rounded-lg px-3 py-2 text-sm" required>
                    <option value="">-- Pilih BU --</option>
                    @foreach($allBusinessUnits as $bu)
                        <option value="{{ $bu->id_bisnis_unit }}">{{ $bu->nama_bisnis_unit }}</option>
                    @endforeach
                </select>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Catatan (opsional)</label>
                <textarea name="note" rows="2" class="w-full border rounded-lg px-3 py-2 text-sm" placeholder="Tambahkan catatan untuk admin BU tujuan..."></textarea>
            </div>
            <div class="flex justify-end gap-2">
                <button type="button" onclick="closeForwardModal()" class="px-4 py-2 bg-gray-200 rounded-lg text-sm">Batal</button>
                <button type="submit" class="px-4 py-2 bg-yellow-600 text-white rounded-lg text-sm">Forward</button>
            </div>
        </form>
    </div>
</div>

<script>
    const transportRadios = document.querySelectorAll('input[name="transport_type"]');
    const companyFields = document.getElementById('company_fields');
    const voucherFields = document.getElementById('voucher_fields');
    const rentalFields = document.getElementById('rental_fields');
    const driverSelect = document.getElementById('driver_select');
    const vehicleSelect = document.getElementById('vehicle_select');
    const voucherSelect = document.getElementById('voucher_select');

    function setRequiredFields(selected) {
        driverSelect.required = false;
        vehicleSelect.required = false;
        voucherSelect.required = false;

        if (selected === 'company_driver') {
            driverSelect.required = true;
            vehicleSelect.required = true;
        } else if (selected === 'voucher') {
            voucherSelect.required = true;
        }
    }

    function toggleFields() {
        const selected = document.querySelector('input[name="transport_type"]:checked')?.value;
        
        companyFields.classList.add('hidden');
        voucherFields.classList.add('hidden');
        rentalFields.classList.add('hidden');

        if (selected === 'company_driver') {
            companyFields.classList.remove('hidden');
        } else if (selected === 'voucher') {
            voucherFields.classList.remove('hidden');
        } else if (selected === 'rental') {
            rentalFields.classList.remove('hidden');
        }
        
        setRequiredFields(selected);
    }

    transportRadios.forEach(radio => {
        radio.addEventListener('change', toggleFields);
    });

    toggleFields();

    // Forward Modal functions
    function openForwardModal() {
        document.getElementById('forwardModal').classList.remove('hidden');
        document.getElementById('forwardModal').style.display = 'flex';
    }

    function closeForwardModal() {
        document.getElementById('forwardModal').classList.add('hidden');
        document.getElementById('forwardModal').style.display = 'none';
    }
</script>
@endsection