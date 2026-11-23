@extends('layouts.app-sidebar')

@section('title', $project->title . ' - Detail Project')

@section('content')
@php
    $isSuper    = Auth::user()->isTaskMonitorSuperadmin();
    $isOther    = (int) $project->user_id !== (int) Auth::id();
    // Superadmin yang melihat project user lain kembali ke daftar milik user itu
    $backUrl    = ($isSuper && $isOther) ? route('task-m.user.projects', $project->user_id) : route('task-m.index');
    $doneCount  = $project->doneUnitsCount();
    $pendCount  = $project->pendingUnitsCount();
    $cancCount  = $project->cancelledUnitsCount();
    $activeCnt  = $project->activeUnitsCount();
    $progress   = $project->progressPercentage();
@endphp

<div class="w-full px-3 sm:px-6 lg:px-8 py-4 sm:py-6">

    {{-- Card Info Project --}}
    <div class="bg-white rounded-2xl shadow-md border border-gray-100 overflow-hidden mb-5">
        <div class="bg-gradient-to-r from-indigo-600 to-indigo-700 px-4 sm:px-6 py-4">
            <div class="flex justify-between items-start gap-3">
                <div class="min-w-0">
                    <h1 class="text-lg sm:text-xl font-semibold text-white break-words">
                        <i class="fas fa-chart-line mr-1"></i> {{ $project->title }}
                    </h1>
                    <p class="text-indigo-100 text-sm mt-1">
                        <i class="far fa-calendar-alt mr-1"></i> Tahun {{ $project->start_date->format('Y') }}
                        @if($isSuper && $isOther && $project->user)
                            &middot; Milik: {{ $project->user->name }}
                        @endif
                    </p>
                </div>
                <div class="flex gap-2 shrink-0">
                    <a href="{{ $backUrl }}" title="Kembali" aria-label="Kembali"
                       class="w-11 h-11 flex items-center justify-center bg-white/20 hover:bg-white/30 text-white rounded-full transition">
                        <i class="fas fa-arrow-left"></i>
                    </a>
                    <button type="button" onclick="focusQuickAdd()" title="Tambah progres" aria-label="Tambah progres"
                            class="w-11 h-11 flex items-center justify-center bg-white/20 hover:bg-white/30 text-white rounded-full transition">
                        <i class="fas fa-plus"></i>
                    </button>
                </div>
            </div>
        </div>

        {{-- Progress --}}
        <div class="p-4 sm:p-6">
            <div class="flex justify-between items-center mb-2">
                <span class="text-sm font-medium text-gray-600">Progress Penyelesaian</span>
                <span class="text-2xl font-bold text-indigo-600">{{ $progress }}%</span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-3 overflow-hidden">
                <div class="bg-indigo-600 h-3 rounded-full transition-all duration-300" style="width: {{ $progress }}%"></div>
            </div>
            <div class="grid grid-cols-3 gap-2 text-center mt-4">
                <div class="rounded-xl bg-green-50 py-2">
                    <p class="text-lg font-semibold text-green-700">{{ $doneCount }}</p>
                    <p class="text-xs text-gray-500">Selesai</p>
                </div>
                <div class="rounded-xl bg-yellow-50 py-2">
                    <p class="text-lg font-semibold text-yellow-700">{{ $pendCount }}</p>
                    <p class="text-xs text-gray-500">Proses</p>
                </div>
                <div class="rounded-xl bg-red-50 py-2">
                    <p class="text-lg font-semibold text-red-700">{{ $cancCount }}</p>
                    <p class="text-xs text-gray-500">Dibatalkan</p>
                </div>
            </div>
            <p class="mt-3 text-xs text-gray-500">
                <i class="fas fa-info-circle text-indigo-400 mr-1"></i>
                Progres = selesai &divide; unit aktif ({{ $doneCount }} dari {{ $activeCnt }}). Unit yang dibatalkan tidak dihitung.
            </p>
        </div>
    </div>

    {{-- Daftar Progres --}}
    <div class="bg-white rounded-2xl shadow-md border border-gray-100 overflow-hidden">
        <div class="px-4 sm:px-6 py-4 bg-gray-50 border-b border-gray-100">
            <h2 class="font-semibold text-gray-800"><i class="fas fa-list-check mr-2 text-indigo-600"></i> Daftar Progres</h2>
        </div>

        @if(session('success'))
            <div class="m-4 p-3 bg-green-100 text-green-700 rounded-xl text-sm">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="m-4 p-3 bg-red-100 text-red-700 rounded-xl text-sm">{{ session('error') }}</div>
        @endif
        @if($errors->any())
            <div class="m-4 p-3 bg-red-100 text-red-700 rounded-xl text-sm">
                <ul class="list-disc pl-5">
                    @foreach($errors->all() as $e)
                        <li>{{ $e }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Tambah cepat (tanpa modal) --}}
        <form action="{{ route('task-m.add-unit', $project->id) }}" method="POST"
              class="p-4 border-b border-gray-100 flex flex-col gap-2 sm:flex-row sm:items-start">
            @csrf
            <textarea id="quickAdd" name="description" rows="2" maxlength="500" required
                      placeholder="Tambah progres baru, contoh: Riset, Desain, Testing..."
                      class="flex-1 rounded-xl border-gray-200 text-sm focus:border-indigo-400 focus:ring focus:ring-indigo-200">{{ old('description') }}</textarea>
            <button type="submit"
                    class="h-11 px-5 rounded-xl bg-indigo-600 text-white text-sm hover:bg-indigo-700 whitespace-nowrap">
                <i class="fas fa-plus mr-1"></i> Tambah
            </button>
        </form>

        @if($project->units->count())
            {{-- Judul kolom (hanya layar lebar) --}}
            <div class="hidden lg:flex items-center gap-4 px-4 py-2 bg-gray-50 border-b border-gray-100 text-xs font-medium text-gray-500 uppercase">
                <div class="w-8">No</div>
                <div class="flex-1">Deskripsi</div>
                <div class="w-28">Status</div>
                <div class="w-40">Waktu</div>
                <div class="w-56 text-right">Aksi</div>
            </div>

            <ul class="divide-y divide-gray-100">
                @foreach($project->units as $unit)
                    @php
                        $isFinal   = $unit->isFinal();
                        $createdAt = $unit->created_at ? $unit->created_at->format('d/m/Y H:i') : '-';
                        $finalAt   = $unit->final_at ? $unit->final_at->format('d/m/Y H:i') : null;
                    @endphp
                    <li class="p-4 flex flex-col gap-3 lg:flex-row lg:items-start lg:gap-4 hover:bg-gray-50">

                        <div class="hidden lg:block w-8 text-sm text-gray-400 pt-0.5">{{ $loop->iteration }}</div>

                        {{-- Deskripsi + edit --}}
                        <div class="flex-1 min-w-0">
                            <div id="view-{{ $unit->id }}" class="flex items-start gap-2">
                                <p class="flex-1 min-w-0 text-gray-800 font-medium break-words">{!! nl2br(e($unit->description)) !!}</p>
                                @unless($isFinal)
                                    <button type="button" onclick="toggleEdit({{ $unit->id }})" title="Ubah deskripsi" aria-label="Ubah deskripsi"
                                            class="shrink-0 w-9 h-9 -mt-1 flex items-center justify-center rounded-full text-gray-400 hover:text-indigo-600 hover:bg-indigo-50">
                                        <i class="fas fa-pen text-sm"></i>
                                    </button>
                                @endunless
                            </div>
                            @unless($isFinal)
                                <form id="edit-form-{{ $unit->id }}" action="{{ route('task-m.update-unit-description', [$project->id, $unit->id]) }}" method="POST" class="hidden">
                                    @csrf @method('PUT')
                                    <textarea name="description" rows="2" maxlength="500" required
                                              class="w-full rounded-xl border-gray-200 text-sm">{{ $unit->description }}</textarea>
                                    <div class="mt-2 flex gap-2">
                                        <button type="submit" class="flex-1 sm:flex-none h-10 px-4 rounded-xl bg-indigo-600 text-white text-sm">Simpan</button>
                                        <button type="button" onclick="toggleEdit({{ $unit->id }})" class="flex-1 sm:flex-none h-10 px-4 rounded-xl border text-sm text-gray-600">Batal</button>
                                    </div>
                                </form>
                            @endunless
                        </div>

                        {{-- Status --}}
                        <div class="lg:w-28 shrink-0">
                            <span class="inline-block text-xs px-2 py-1 rounded-full {{ $unit->status_class }}">{{ $unit->status_label }}</span>
                            @if($isFinal)
                                <i class="fas fa-lock ml-1 text-xs text-gray-400" title="Final"></i>
                            @endif
                        </div>

                        {{-- Waktu --}}
                        <div class="lg:w-40 shrink-0 text-xs text-gray-500 leading-5">
                            <div>Dibuat: {{ $createdAt }}</div>
                            @if($finalAt)
                                <div>{{ $unit->status === 'done' ? 'Selesai' : 'Batal' }}: {{ $finalAt }}</div>
                            @endif
                        </div>

                        {{-- Aksi --}}
                        <div class="flex gap-2 lg:w-56 shrink-0 lg:justify-end">
                            @if(!$isFinal)
                                <form action="{{ route('task-m.update-unit-status', [$project->id, $unit->id]) }}" method="POST"
                                      class="flex-1 lg:flex-none js-confirm"
                                      data-title="Tandai selesai?" data-desc="{{ \Illuminate\Support\Str::limit($unit->description, 100) }}"
                                      data-ok="Ya, selesai" data-tone="green">
                                    @csrf @method('PATCH')
                                    <input type="hidden" name="action" value="done">
                                    <button type="submit"
                                            class="w-full h-11 lg:h-10 px-4 rounded-xl bg-green-500 hover:bg-green-600 text-white text-sm font-medium inline-flex items-center justify-center gap-2">
                                        <i class="fas fa-check"></i> Selesai
                                    </button>
                                </form>
                                <form action="{{ route('task-m.update-unit-status', [$project->id, $unit->id]) }}" method="POST"
                                      class="flex-1 lg:flex-none js-confirm"
                                      data-title="Batalkan progres ini?" data-desc="{{ \Illuminate\Support\Str::limit($unit->description, 100) }}"
                                      data-ok="Ya, batalkan" data-tone="red">
                                    @csrf @method('PATCH')
                                    <input type="hidden" name="action" value="cancel">
                                    <button type="submit"
                                            class="w-full h-11 lg:h-10 px-4 rounded-xl bg-red-500 hover:bg-red-600 text-white text-sm font-medium inline-flex items-center justify-center gap-2">
                                        <i class="fas fa-times"></i> Batal
                                    </button>
                                </form>
                            @else
                                <span class="text-xs text-gray-400 bg-gray-100 px-3 py-1.5 rounded-full">
                                    <i class="fas fa-check-circle mr-1"></i> Final
                                </span>
                            @endif
                        </div>
                    </li>
                @endforeach
            </ul>
        @else
            <div class="p-10 text-center text-gray-400">
                <i class="fas fa-clipboard-list text-4xl mb-3"></i>
                <p>Belum ada progres. Tulis di kolom di atas lalu ketuk Tambah.</p>
            </div>
        @endif
    </div>
</div>

{{-- Konfirmasi Selesai / Batal (status bersifat final) --}}
<div id="confirmModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-end sm:items-center justify-center z-50 p-4"
     role="dialog" aria-modal="true" aria-labelledby="cmTitle">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-sm p-5">
        <h3 id="cmTitle" class="text-lg font-bold text-gray-800"></h3>
        <p id="cmDesc" class="mt-2 text-sm text-gray-700 break-words"></p>
        <p class="mt-2 text-xs text-gray-500">Status final dan tidak bisa diubah lagi setelah disimpan.</p>
        <div class="mt-4 flex gap-2">
            <button type="button" id="cmCancel" class="flex-1 h-11 rounded-xl border text-gray-700">Kembali</button>
            <button type="button" id="cmOk" class="flex-1 h-11 rounded-xl text-white font-medium"></button>
        </div>
    </div>
</div>

<script>
    function focusQuickAdd() {
        var el = document.getElementById('quickAdd');
        el.scrollIntoView({ behavior: 'smooth', block: 'center' });
        el.focus();
    }

    function toggleEdit(id) {
        var view = document.getElementById('view-' + id);
        var form = document.getElementById('edit-form-' + id);
        view.classList.toggle('hidden');
        form.classList.toggle('hidden');
        if (!form.classList.contains('hidden')) {
            var ta = form.querySelector('textarea');
            ta.focus();
            ta.setSelectionRange(ta.value.length, ta.value.length);
        }
    }

    (function () {
        var modal = document.getElementById('confirmModal');
        var okBtn = document.getElementById('cmOk');
        var pending = null;

        function openConfirm(form) {
            pending = form;
            document.getElementById('cmTitle').textContent = form.dataset.title;
            document.getElementById('cmDesc').textContent = form.dataset.desc || '';
            okBtn.textContent = form.dataset.ok;
            okBtn.disabled = false;
            okBtn.className = 'flex-1 h-11 rounded-xl text-white font-medium ' +
                (form.dataset.tone === 'red' ? 'bg-red-500 hover:bg-red-600' : 'bg-green-500 hover:bg-green-600');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            okBtn.focus();
        }
        function closeConfirm() {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            pending = null;
        }

        document.querySelectorAll('form.js-confirm').forEach(function (f) {
            f.addEventListener('submit', function (e) {
                e.preventDefault();
                openConfirm(f);
            });
        });
        okBtn.addEventListener('click', function () {
            if (!pending) return;
            okBtn.disabled = true;      // cegah tap ganda
            pending.submit();
        });
        document.getElementById('cmCancel').addEventListener('click', closeConfirm);
        modal.addEventListener('click', function (e) { if (e.target === modal) closeConfirm(); });
        document.addEventListener('keydown', function (e) { if (e.key === 'Escape') closeConfirm(); });
    })();
</script>
@endsection
