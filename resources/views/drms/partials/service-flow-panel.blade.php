{{--
    Panel status pengajuan Servis Rutin / Perbaikan.
    Dipakai di: service_schedules/show, repairs/show (admin) dan drivers/service_requests/show (driver).
    Parameter: $type ('service'|'repair'), $item (ServiceSchedule|Repair)
    Tombol aksi hanya tampil untuk admin DRMS (gate yang sama dengan route: isDrmsAdmin).
--}}
@php
    $flowLabels = ['open' => 'Menunggu Approval Admin', 'progress' => 'Dalam Proses', 'done' => 'Selesai', 'rejected' => 'Ditolak'];
    $flowColors = ['open' => 'bg-yellow-100 text-yellow-800', 'progress' => 'bg-blue-100 text-blue-800', 'done' => 'bg-green-100 text-green-800', 'rejected' => 'bg-red-100 text-red-800'];
    $isRepair = $type === 'repair';
@endphp

<div class="mt-6 border-t pt-4">
    @if(session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">{{ session('error') }}</div>
    @endif
    @if($errors->any())
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            <ul class="list-disc pl-5">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    <h2 class="text-lg font-semibold mb-3">Status Pengajuan</h2>
    <div class="flex flex-wrap items-center gap-2 mb-3">
        <span class="px-3 py-1 rounded-full text-sm font-semibold {{ $flowColors[$item->status] ?? 'bg-gray-100 text-gray-800' }}">
            {{ $flowLabels[$item->status] ?? $item->status }}
        </span>
    </div>

    <dl class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm">
        @if($item->approved_by && $item->approver)
            <div>
                <dt class="text-gray-500">{{ $item->status === 'rejected' ? 'Ditolak oleh' : 'Disetujui oleh' }}</dt>
                <dd class="font-medium">{{ $item->approver->name }}
                    @if($item->approved_at)<span class="text-gray-500 text-xs">({{ $item->approved_at->timezone('Asia/Jakarta')->format('d M Y H:i') }})</span>@endif
                </dd>
            </div>
        @endif
        @if($item->completed_by && $item->completer)
            <div>
                <dt class="text-gray-500">Diselesaikan oleh</dt>
                <dd class="font-medium">{{ $item->completer->name }}
                    @if($item->completed_at)<span class="text-gray-500 text-xs">({{ $item->completed_at->timezone('Asia/Jakarta')->format('d M Y H:i') }})</span>@endif
                </dd>
            </div>
        @endif
        @if($item->status === 'rejected' && $item->rejection_reason)
            <div class="sm:col-span-2">
                <dt class="text-gray-500">Alasan penolakan</dt>
                <dd class="text-red-600">{{ $item->rejection_reason }}</dd>
            </div>
        @endif
    </dl>

    @can('isDrmsAdmin')
        {{-- ===== Admin: pengajuan baru -> Setujui / Tolak ===== --}}
        @if($item->status === 'open')
            <div class="mt-4 flex flex-wrap items-start gap-3">
                <form action="{{ route('drms.service-flow.approve', [$type, $item->id]) }}" method="POST"
                      onsubmit="return confirm('Setujui pengajuan ini? Status akan menjadi Dalam Proses.')">
                    @csrf
                    <button type="submit" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg text-sm font-semibold">Setujui</button>
                </form>

                <details class="border rounded-lg p-3 bg-red-50">
                    <summary class="cursor-pointer text-sm font-semibold text-red-700">Tolak</summary>
                    <form action="{{ route('drms.service-flow.reject', [$type, $item->id]) }}" method="POST" class="mt-3 space-y-2">
                        @csrf
                        <textarea name="rejection_reason" rows="2" required maxlength="1000"
                                  class="w-full border rounded px-3 py-2 text-sm" placeholder="Alasan penolakan (wajib)"></textarea>
                        <button type="submit" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg text-sm font-semibold">Kirim Penolakan</button>
                    </form>
                </details>
            </div>
        @endif

        {{-- ===== Admin: sedang proses -> Selesaikan ===== --}}
        @if($item->status === 'progress')
            <details class="mt-4 border rounded-lg p-4 bg-blue-50" open>
                <summary class="cursor-pointer text-sm font-semibold text-blue-800">Selesaikan Pekerjaan</summary>
                <form action="{{ route('drms.service-flow.complete', [$type, $item->id]) }}" method="POST" enctype="multipart/form-data" class="mt-3 grid grid-cols-1 sm:grid-cols-2 gap-3">
                    @csrf
                    @if(!$isRepair)
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Biaya (Rp) <span class="text-red-500">*</span></label>
                            <input type="number" name="cost" min="0" step="0.01" required value="{{ old('cost') }}" class="w-full border rounded px-3 py-2 text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Bengkel</label>
                            <input type="text" name="workshop_name" maxlength="255" value="{{ old('workshop_name', $item->workshop_name) }}" class="w-full border rounded px-3 py-2 text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Odometer saat servis (km)</label>
                            <input type="number" name="odometer_at_service" min="0" value="{{ old('odometer_at_service', $item->odometer_at_service) }}" class="w-full border rounded px-3 py-2 text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Servis berikutnya (odometer)</label>
                            <input type="number" name="next_service_odometer" min="0" value="{{ old('next_service_odometer') }}" class="w-full border rounded px-3 py-2 text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Servis berikutnya (tanggal)</label>
                            <input type="date" name="next_service_date" value="{{ old('next_service_date') }}" class="w-full border rounded px-3 py-2 text-sm">
                        </div>
                    @else
                        <div class="sm:col-span-2">
                            <label class="block text-xs font-medium text-gray-600 mb-1">Diagnosa bengkel</label>
                            <textarea name="diagnosis" rows="2" class="w-full border rounded px-3 py-2 text-sm">{{ old('diagnosis', $item->diagnosis) }}</textarea>
                        </div>
                        <div class="sm:col-span-2">
                            <label class="block text-xs font-medium text-gray-600 mb-1">Sparepart diganti</label>
                            <textarea name="parts_replaced" rows="2" class="w-full border rounded px-3 py-2 text-sm">{{ old('parts_replaced', $item->parts_replaced) }}</textarea>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Biaya jasa (Rp)</label>
                            <input type="number" name="labor_cost" min="0" step="0.01" value="{{ old('labor_cost', $item->labor_cost) }}" class="w-full border rounded px-3 py-2 text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Biaya sparepart (Rp)</label>
                            <input type="number" name="parts_cost" min="0" step="0.01" value="{{ old('parts_cost', $item->parts_cost) }}" class="w-full border rounded px-3 py-2 text-sm">
                        </div>
                    @endif
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-medium text-gray-600 mb-1">Catatan penyelesaian</label>
                        <textarea name="completion_notes" rows="2" maxlength="2000" class="w-full border rounded px-3 py-2 text-sm">{{ old('completion_notes') }}</textarea>
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-medium text-gray-600 mb-1">Invoice / nota (JPG, PNG, PDF - maks 5 MB)</label>
                        <input type="file" name="invoice_file" accept="image/*,application/pdf" class="w-full border rounded px-3 py-2 text-sm bg-white">
                    </div>
                    <div class="sm:col-span-2 flex justify-end">
                        <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-semibold"
                                onclick="return confirm('Tandai pekerjaan ini selesai?')">Tandai Selesai</button>
                    </div>
                </form>
            </details>
        @endif
    @endcan
</div>
