@php
    $role = session('hsrm_role', 'pic');
    $isAdmin = $role === 'admin';

    // Jumlah data berstatus Revision di area milik PIC (badge merah di menu).
    $revCert = 0;
    $revEq = 0;
    if ($role === 'pic') {
        $revAreaIds = auth()->user()->hsrmAreas->pluck('id_area_kerja');
        if ($revAreaIds->isNotEmpty()) {
            $revCert = \App\Models\HSRM\HsrmCertificate::where('status_verif', 'revision')->whereIn('area_id', $revAreaIds)->count();
            $revEq = \App\Models\HSRM\HsrmEquipment::where('status_verif', 'revision')->whereIn('area_id', $revAreaIds)->count();
        }
    }
@endphp

<aside class="sidebar fixed h-full bg-white border-r overflow-hidden z-50 hidden md:flex md:flex-col">
    <div class="p-6 shrink-0 border-b flex items-center space-x-3">
        <div class="w-10 h-10 bg-blue-50 rounded-lg flex items-center justify-center border">
            <span class="font-bold text-blue-600">HSR</span>
        </div>
        <div>
            <h1 class="text-xl font-bold text-gray-800">HSR Management</h1>
            <p class="text-xs text-gray-500">{{ $isAdmin ? 'Admin' : 'PIC' }}</p>
        </div>
    </div>

    <nav class="p-4 flex-1 min-h-0 overflow-y-auto">
        <ul class="space-y-1">
            <li>
                <a href="{{ route('hsrm.dashboard') }}" class="sidebar-link flex items-center p-3 rounded-lg text-gray-700 {{ request()->routeIs('hsrm.dashboard') ? 'active' : '' }}">
                    <i class="fas fa-home mr-3 text-gray-400"></i> Dashboard
                </a>
            </li>
            <li>
                <a href="{{ $revCert > 0 ? route('hsrm.certificates.index', ['status_verif' => 'revision']) : route('hsrm.certificates.index') }}" class="sidebar-link flex items-center p-3 rounded-lg text-gray-700 {{ request()->routeIs('hsrm.certificates.*') ? 'active' : '' }}">
                    <i class="fas fa-file-alt mr-3 text-gray-400"></i> Certificates
                    @if($revCert > 0)
                        <span class="ml-auto bg-red-600 text-white text-xs font-semibold rounded-full px-2 py-0.5" title="{{ $revCert }} certificate(s) need revision" aria-label="{{ $revCert }} certificates need revision">{{ $revCert }}</span>
                    @endif
                </a>
            </li>
            <li>
                <a href="{{ $revEq > 0 ? route('hsrm.equipments.index', ['status_verif' => 'revision']) : route('hsrm.equipments.index') }}" class="sidebar-link flex items-center p-3 rounded-lg text-gray-700 {{ request()->routeIs('hsrm.equipments.*') ? 'active' : '' }}">
                    <i class="fas fa-fire-extinguisher mr-3 text-gray-400"></i> Equipments
                    @if($revEq > 0)
                        <span class="ml-auto bg-red-600 text-white text-xs font-semibold rounded-full px-2 py-0.5" title="{{ $revEq }} equipment(s) need revision" aria-label="{{ $revEq }} equipments need revision">{{ $revEq }}</span>
                    @endif
                </a>
            </li>
            @if($isAdmin)
                <li>
                    <a href="{{ route('hsrm.certificate-types.index') }}" class="sidebar-link flex items-center p-3 rounded-lg text-gray-700 {{ request()->routeIs('hsrm.certificate-types.*') ? 'active' : '' }}">
                        <i class="fas fa-tags mr-3 text-gray-400"></i> Certificate Types
                    </a>
                </li>
                <li>
                    <a href="{{ route('hsrm.logs.index') }}" class="sidebar-link flex items-center p-3 rounded-lg text-gray-700 {{ request()->routeIs('hsrm.logs.*') ? 'active' : '' }}">
                        <i class="fas fa-history mr-3 text-gray-400"></i> Logs
                    </a>
                </li>
            @endif
        </ul>
    </nav>

    <div class="shrink-0 p-4 border-t bg-white">
        <div class="flex items-center space-x-3">
            <div class="w-10 h-10 rounded-full bg-blue-50 flex items-center justify-center border">
                <span class="font-semibold text-blue-600">{{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 2)) }}</span>
            </div>
            <div>
                <h4 class="font-medium text-gray-800">{{ auth()->user()->name }}</h4>
                <p class="text-xs text-gray-500">{{ $isAdmin ? 'Admin' : 'PIC' }}</p>
            </div>
        </div>
    </div>
</aside>